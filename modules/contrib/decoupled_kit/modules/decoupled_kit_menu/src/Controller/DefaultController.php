<?php

namespace Drupal\decoupled_kit_menu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Class DefaultController.
 */
class DefaultController extends ControllerBase {

  /**
   * Drupal\Core\Menu\MenuLinkTreeInterface definition.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->menuLinkTree = $container->get('menu.link_tree');
    return $instance;
  }

  /**
   * Get menu data.
   *
   * @param string $id
   *   Menu id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return menu data.
   */
  public function index($id) {
    $language = $this->languageManager()->getCurrentLanguage()->getId();
    $cid = sprintf('decoupled_kit_menu:%s_%s', $id, $language);
    $cache = $this->cache()->get($cid);
    if ($cache) {
      return new JsonResponse($cache->data);
    }

    $parameters = $this->menuLinkTree->getCurrentRouteMenuTreeParameters($id);
    $tree = $this->menuLinkTree->load($id, $parameters);

    if (!$tree) {
      throw new NotFoundHttpException($this->t('Cannot load menu @id', ['@id' => $id]));
    }

    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuLinkTree->transform($tree, $manipulators);
    $menu = $this->menuLinkTree->build($tree);

    // Get menu item url for all levels of the menu.
    if (!empty($menu['#items'])) {
      $menu['#items'] = $this->buildMenu($menu['#items']);
    }

    $array = [
      'id' => $id,
      'data' => $menu,
    ];

    // Save to cache.
    $this->cache()->set($cid, $array, CacheBackendInterface::CACHE_PERMANENT, $menu['#cache']['tags']);

    return new JsonResponse($array);
  }

  /**
   * Recursive function to get menu items url from all levels.
   *
   * @param array $menu_items
   *   Array of menu items.
   *
   * @return array
   *   Array of menu items with url.
   */
  private function buildMenu(array $menu_items) {
    foreach ($menu_items as $key => $item) {
      $menu_items[$key]['url'] = str_replace('/drupal', '', $item['url']->toString());
      if (!empty($menu_items[$key]['below'])) {
        // There's items below. Call funtions recursively to build next level.
        $menu_items[$key]['below'] = $this->buildMenu($menu_items[$key]['below']);
      }
    }
    return $menu_items;
  }

}
