<?php

namespace Drupal\restuiextention\Normalizer;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\serialization\Normalizer\ContentEntityNormalizer;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityManagerInterface;
/**
 * Controller routines for REST EUI Extension resources.
 */
class CustomTypedDataNormalizer extends ContentEntityNormalizer {

  /**
   * The Config Factory service.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
    protected  $config_factory;
	
  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
    protected $entityManager;

  /**
   * The Logger Channel Factory service.
   *
   * @var Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
	protected $logger;

 
  /**
   * Constructs a CustomTypedDataNormalizer object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The REST resource entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Configuration provider.
   * @param Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The Logger generator.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger) {
    $this->entityManager = $entity_manager;
    $this->config = $config_factory->get('restuiextention.basic.settings');
    $this->logger = $logger;
  }

  /**
   * Injects RestUIExtention Service.
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('entity.manager'),
        $container->get('config.factory'),
        $container->get('logger.factory')
    );
  }

  /**
   * The interface or class that this Normalizer supports.
   *
   * @param array $entity
   *   The identifier or the REST resource.
   * @param array $format
   *   The format to check.
   * @param array $context
   *   Context options for the normalizer.
   * @return array
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Access is denied, if the token is invalid or missing.
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $attributes = parent::normalize($entity, $format, $context);
    $final = [];
	
    foreach ($attributes as $key => $value) {
      $term_name = '';
	  //kint($value);
	  $term_name_array = array();
	  $target_id_array = array();
	  for($i=0; $i<count($value);$i++){
		  foreach ($value[$i] as $k => $val) {
			if ($k != 'value') {
			  $final[$key][$k] = $val;
			  if($value[$i]['target_type'] == 'taxonomy_term'){
				  $tid = $value[$i]['target_id'];
				  if (isset($tid)) {
					$term = $this->entityManager->getStorage('taxonomy_term')->load($tid);
					if (isset($term)) {
					  array_push($term_name_array, $term->name->value);
					  $term_type = $term->getEntityTypeId();
					  $final[$key]['target_type'] = $term_type;
					}
				  }
			  }
			  else{
				array_push($target_id_array, $value[$i]['target_id']);
			  }
			}
			else {
			  $final[$key] = $val;
			}
		  }
	  }
	  $term_name = implode(',', array_unique($term_name_array));
	  $target_id = implode(',', array_unique($target_id_array));
	  if($term_name != ''){
	      $final[$key]['target_id'] = $term_name;
	  }
	  else{
	      $final[$key]['target_id'] = $target_id;
	  }
    }
	$final['status'] = '200';
    $final['message'] = 'Success';
	
	//Logging to the dblog
	$enable_log = $this->config->get('enable_log');
	if($enable_log){		
		$this->logger->get('restuiextension_get_service_call')->info('Response : <br /> %log.',
        array(
            '%log' => json_encode($final),
        ));
	}
    return $final;
  }

  /**
   * The interface or class that this Denormalizer supports.
   *
   * @param array $data
   *   The identifier for the REST resource.
   * @param array $class
   *   The identifier for the REST resource class.
   * @param array $format
   *   The format to check.
   * @param array $context
   *   Context options for the normalizer.
   * @return array
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Access is denied, if the token is invalid or missing.
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
	$term_name_array = array();
    foreach ($data as $key => $value) {
    $collect[$key] = array();
      if (isset($value['target_id'])) {
        if ($value['target_type'] == 'taxonomy_term') {
			$term_name_array = explode(',', $value['target_id']);
			$cntTerm = count($term_name_array);
			for($i=0; $i<$cntTerm ; $i++){
			  $term_name = $term_name_array[$i];
			  $terms = $this->entityManager->getStorage('taxonomy_term')->loadByProperties(['name' => $term_name]);
			  $term = reset($terms);
			  $this->array_push_associative($collect[$key], array($i => array('target_id' => $term->id())));
			}          
        }
        else {
			$term_name_array = explode(',', $value['target_id']);
			$cntTerm = count($term_name_array);
			for($i=0; $i<$cntTerm ; $i++){
				$this->array_push_associative($collect[$key], array($i => array('target_id' => $term_name_array[$i])));
			}
        }
      }
      else {
        $collect[$key] = [
          '0' => ['value' => $value],
        ];
      }
    }
	
    $return = parent::denormalize($collect, $class, $format, $context);
	
	//Logging to the dblog
	$enable_log = $this->config->get('enable_log');
	if($enable_log){	
		$this->logger->get('restuiextension_post_service_call')->info('Request : <br /> %log.',
        array(
            '%log' => json_encode($data),
        ));
	}
    return $return;
  }

  /**
   * The function to push associateve array
   *
   * @param array $data
   *   The associative array to be pushed
   *
   * @return array
   * 
   */
  public function array_push_associative(&$arr) {
   $args = func_get_args();
   foreach ($args as $arg) {
       if (is_array($arg)) {
           foreach ($arg as $key => $value) {
               $arr[$key] = $value;
               $ret++;
           }
       }else{
           $arr[$arg] = "";
       }
   }
   return $ret;
 }
}
