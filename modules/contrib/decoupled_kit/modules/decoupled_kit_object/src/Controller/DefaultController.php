<?php

namespace Drupal\decoupled_kit_object\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;

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
   * Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface definition.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->decoupledKit = $container->get('decoupled_kit');
    $instance->pathAliasManager = $container->has('path_alias.manager') ?
      $container->get('path_alias.manager') :
      $container->get('path.alias_manager');
    if ($container->has('jsonapi.resource_type.repository')) {
      $instance->resourceTypeRepository = $container->get('jsonapi.resource_type.repository');
    }
    return $instance;
  }

  /**
   * Get entity object data for current page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return entity object data.
   */
  public function index(Request $request) {
    $path = $this->decoupledKit->checkPath($request);

    $array = [];
    $internal_path = $this->pathAliasManager->getPathByAlias($path);
    $entity = $this->decoupledKit->getEntityFromPath($internal_path, FALSE);
    if ($entity) {
      $allowed = $this->decoupledKit->allowEntity($entity);
      if ($allowed) {
        $object = $this->getEntityData($entity);
        if (!empty($object)) {
          $array = [
            'path' => $path,
            'data' => $object,
          ];
        }
      }
      else {
        $array = [
          'error' => $this->t('Not allowed'),
        ];
      }
    }

    return new JsonResponse($array);
  }

  /**
   * Get entity data for current page.
   *
   * @param object $entity
   *   Entity object.
   *
   * @return array
   *   Return entity data.
   */
  protected function getEntityData($entity) {
    $entityTypeId = $entity->getEntityTypeId();
    $uuid = $entity->uuid();
    $bundle = $entity->bundle();
    $data = [
      'id' => $entity->id(),
      'uuid' => $uuid,
      'type' => $entityTypeId,
      'bundle' => $bundle,
    ];

    $processed = FALSE;

    // Found at router from decoupled kit routers list.
    if ($this->moduleHandler()->moduleExists('decoupled_kit_router')) {
      $result = $this->entityTypeManager()
        ->getStorage('decoupled_kit_router')
        ->loadByProperties([
          'entity_type' => $entityTypeId,
          'entity_bundle' => $bundle,
          'status' => 1,
        ]);

      if ($result) {
        $entity = reset($result);
        $link = Url::fromUserInput(
          sprintf('%s/%s', $entity->get('router_path'), $uuid),
          ['absolute' => TRUE]
        )->toString(TRUE);
        $data['link'] = $link->getGeneratedUrl();
        $data['provider'] = 'decoupled_kit_router';

        $processed = TRUE;
      }
    }

    // Else JSON API support.
    if (!$processed && !empty($this->resourceTypeRepository)) {
      $typeName = $this->resourceTypeRepository->get($entityTypeId, $bundle)->getTypeName();
      $routeName = sprintf('jsonapi.%s.individual', $typeName);
      $link = Url::fromRoute(
        $routeName,
        [static::getEntityRouteParameterName($routeName, $entityTypeId) => $uuid],
        ['absolute' => TRUE]
      )->toString(TRUE);
      $data['link'] = $link->getGeneratedUrl();
      $data['provider'] = 'jsonapi';
    }

    return $data;
  }

  /**
   * Computes the name of the entity route parameter for JSON API routes.
   *
   * @param string $route_name
   *   A JSON API route name.
   * @param string $entity_type_id
   *   The corresponding entity type ID.
   *
   * @return string
   *   Either 'entity' or $entity_type_id.
   */
  protected static function getEntityRouteParameterName($route_name, $entity_type_id) {
    static $first;

    if (!isset($first)) {
      $routeProvider = \Drupal::service('router.route_provider');

      $routeParameters = $routeProvider
        ->getRouteByName($route_name)
        ->getOption('parameters');
      $first = isset($routeParameters['entity']) ? 'entity' : $entity_type_id;
      return $first;
    }

    return $first === 'entity' ? 'entity' : $entity_type_id;
  }

}
