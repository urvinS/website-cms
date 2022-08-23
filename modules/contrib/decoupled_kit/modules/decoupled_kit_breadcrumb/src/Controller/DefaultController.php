<?php

namespace Drupal\decoupled_kit_breadcrumb\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;
use Drupal\views\Views;

/**
 * Class for DefaultController.
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
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->decoupledKit = $container->get('decoupled_kit');
    $instance->pathAliasManager = $container->get('path.alias_manager');
    $instance->pathValidator = $container->get('path.validator');
    return $instance;
  }

  /**
   * Get breadcrumb data for current page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return breadcrumbs.
   */
  public function index(Request $request) {
    $path = $this->decoupledKit->checkPath($request);
    $config = $this->config('decoupled_kit_breadcrumb.config');
    $breadcrumbs = [];

    if ($path === '/') {
      $breadcrumbs[] = ['title' => $this->t('Home')];
    }
    else {
      $path_patterns = [];
      $configPathPatterns = trim($config->get('path_patterns'));
      if (!empty($configPathPatterns)) {
        $path_patterns = explode("\n", $configPathPatterns);
      }

      // Front page part.
      if ($config->get('need_front')) {
        $breadcrumbs[] = [
          'link' => '/',
          'title' => $this->t('Home'),
        ];
      }

      // Main part.
      $main = $this->getBreadcrumbs($path, $path_patterns, $config->get('use_bundle_name'));
      if (!empty($main)) {
        foreach ($main as $value) {
          $breadcrumbs[] = $value;
        }
      }

      // Title part.
      if ($config->get('need_title')) {
        $title = $this->getTitle($path, $path_patterns);
        if (!empty($title)) {
          $breadcrumbs[] = [
            'title' => $title,
          ];
        }
      }
    }

    // Check for valid links.
    if ($config->get('need_url_validate')) {
      $breadcrumbs = $this->checkLinks($breadcrumbs);
    }

    $array = [
      'path' => $path,
      'data' => $breadcrumbs,
    ];

    return new JsonResponse($array);
  }

  /**
   * Get breadcrumb title.
   *
   * @param string $path
   *   Input path.
   * @param array $path_patterns
   *   Patterns from config.
   *
   * @return string|bool
   *   Title string or null.
   */
  protected function getTitle($path, array $path_patterns) {
    $internal_path = $this->pathAliasManager->getPathByAlias($path);
    if ($internal_path && $internal_path != $path) {
      $entity = $this->decoupledKit->getEntityFromPath($internal_path);
      if (!empty($entity)) {
        $title = NULL;
        $entity_type = $entity->getEntityTypeId();
        switch ($entity_type) {
          case 'taxonomy_term':
            $title = $entity->getName();
            break;

          case 'node':
            $title = $entity->getTitle();

          default:
            try {
              $title = $entity->getTitle();
            }
            catch (\Exception $e) {
              return NULL;
            }
        }

        if (!$title) {
          return NULL;
        }
        return $title;
      }
    }
    else {
      if (!empty($path_patterns)) {
        // Get title from path patterns.
        foreach ($path_patterns as $value) {
          [, $link, $title] = explode('|', $value);
          $link = $this->decoupledKit->canonicalPath($link);

          if ($path == $link) {
            return trim($title);
          }
        }
      }
    }

    return NULL;
  }

  /**
   * Get main part.
   *
   * @param string $path
   *   Input path.
   * @param array $path_patterns
   *   Patterns from config.
   * @param bool $use_entity_bundle
   *   Patterns from config.
   *
   * @return array|bool
   *   Main part of breadcrumb or NULL.
   */
  protected function getBreadcrumbs($path, array $path_patterns, $use_entity_bundle) {
    $parts = explode('/', $path);

    // Remove first and last elements.
    array_shift($parts);
    array_pop($parts);

    $internal_path = $this->pathAliasManager->getPathByAlias($path);
    $entity = $this->decoupledKit->getEntityFromPath($internal_path);

    // Add entity bundle name if url is a simple.
    if ($use_entity_bundle && empty($parts) && !empty($entity)) {
      $entity_bundle = $entity->bundle();
      if (!empty($entity_bundle)) {
        $parts[] = $entity_bundle;
      }
    }

    if (empty($parts)) {
      return NULL;
    }

    $res = [];

    // Breadcrumbs from routes.
    $parts2 = [];
    foreach ($parts as $part) {
      $parts2[] = $part;
      $url = '/' . implode('/', $parts2);
      $title = $this->getTitleFromPath($url);
      if (!empty($title)) {
        $res[$part] = [
          'link' => $url,
          'title' => $title,
        ];
      }
    }

    // Breadcrumbs from path patterns settings.
    foreach ($parts as $part) {
      if (!empty($path_patterns)) {
        foreach ($path_patterns as $pattern) {
          [$type, $link, $title] = explode('|', $pattern);
          if (trim($type) == $part) {
            $res[$part] = [
              'link' => $this->decoupledKit->canonicalPath($link),
              'title' => trim($title),
            ];
            break;
          }
        }
      }
    }

    // Breadcrumbs from bundle title.
    if ($use_entity_bundle && empty($res) && !empty($entity)) {
      $entity_bundle = $entity->bundle();
      if (!empty($entity_bundle)) {
        $entity_bundle_title = $entity->type->entity->label();
        $res[] = [
          'link' => $this->decoupledKit->canonicalPath($entity_bundle),
          'title' => $this->t($entity_bundle_title),
        ];
      }
    }

    return $res;
  }

  /**
   * Get title from path.
   *
   * @param string $path
   *   Input path.
   *
   * @return string|bool
   *   Title string.
   */
  protected function getTitleFromPath($path) {
    try {
      $routeName = Url::fromUri("internal:" . $path)->getRouteName();
    }
    catch (\Exception $e) {
      return NULL;
    }

    $title = NULL;
    $routeParts = explode('.', $routeName);
    switch ($routeParts[0]) {
      case 'entity':
        $title = $this->getTitle($path, []);
        break;

      case 'view':
        $viewName = $routeParts[1];
        $displayId = $routeParts[2];
        $view = Views::getView($viewName);
        if (!$view || !$view->access($displayId)) {
          return NULL;
        }
        $view->setDisplay($displayId);
        $title = $view->getTitle();
        break;
    }

    return $title;
  }

  /**
   * Check for valid links.
   *
   * @param array $breadcrumbs
   *   Breadcrumbs array.
   *
   * @return array
   *   Breadcrumbs array with valid links.
   */
  protected function checkLinks(array $breadcrumbs) {
    $res = [];
    foreach ($breadcrumbs as $breadcrumb) {
      $link = $breadcrumb['link'];
      if (empty($link) || $this->pathValidator->isValid($link)) {
        $res[] = $breadcrumb;
      }
    }
    return $res;
  }

}
