<?php

namespace Drupal\decoupled_kit_sitemap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Url;

/**
 * Class DefaultController.
 */
class DefaultController extends ControllerBase {

  /**
   * Get sitemap data.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return sitemap data.
   */
  public function index() {
    $config = $this->config('decoupled_kit_sitemap.config');
    $items = $config->get('items');

    $array = [];
    if (!empty($items)) {
      $items = json_decode($items, TRUE);
      if (!empty($items)) {
        foreach ($items as $entity => $values) {
          foreach ($values as $value) {
            $url = $this->getUrl($entity, $value);
            if ($url) {
              $array[$entity][$value] = $url;
            }
          }
        }
      }
    }

    return new JsonResponse($array);
  }

  /**
   * Get URL.
   *
   * @param string $entity
   *   Entity (menu, vocabulary...)
   * @param string $value
   *   Entity value.
   *
   * @return string
   *   Return URL string.
   */
  protected function getUrl($entity, $value) {
    $route = NULL;
    switch ($entity) {
      case 'menu':
        $route = 'decoupled_kit_menu.index';
        break;

      case 'vocabulary':
        $route = 'decoupled_kit_taxonomy.index';
        break;
    }
    if (!$route) {
      return NULL;
    }

    $url = Url::fromRoute($route, ['id' => $value]);
    return $url->toString(TRUE)->getGeneratedUrl();
  }

}
