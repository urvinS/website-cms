<?php

namespace Drupal\decoupled_kit_metatag\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DefaultController.
 */
class DefaultController extends ControllerBase {

  /**
   * Drupal\Core\DependencyInjection\ContainerInjectionInterface definition.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerInjectionInterface
   */
  protected $decoupledKit;

  /**
   * Drupal\Core\Path\AliasManagerInterface definition.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $pathAliasManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->decoupledKit = $container->get('decoupled_kit');
    $instance->pathAliasManager = $container->get('path.alias_manager');
    return $instance;
  }

  /**
   * Get metatag data for current page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return string
   *   Return Hello string.
   */
  public function index(Request $request) {
    $path = $this->decoupledKit->checkPath($request);

    $array = [];
    $internal_path = $this->pathAliasManager->getPathByAlias($path);
    $entity = $this->decoupledKit->getEntityFromPath($internal_path);
    if ($entity) {
      $metatags = metatag_get_tags_from_route($entity);
      if (!empty($metatags)) {
        $array = [
          'path' => $path,
          'data' => $metatags,
        ];
      }
    }

    return new JsonResponse($array);
  }

}
