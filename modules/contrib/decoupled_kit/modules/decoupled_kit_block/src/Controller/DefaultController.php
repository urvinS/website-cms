<?php

namespace Drupal\decoupled_kit_block\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Url;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\block\BlockRepositoryInterface;

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
   * Drupal\Core\Block\BlockManagerInterface definition.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $pluginManagerBlock;

  /**
   * Drupal\Core\Theme\ThemeManagerInterface definition.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Drupal\Core\Path\PathMatcherInterface definition.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Drupal\Core\Render\RendererInterface definition.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->decoupledKit = $container->get('decoupled_kit');
    $instance->pluginManagerBlock = $container->get('plugin.manager.block');
    $instance->themeManager = $container->get('theme.manager');
    $instance->pathMatcher = $container->get('path.matcher');
    $instance->renderer = $container->get('renderer');
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  /**
   * Get blocks data for current page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return block data.
   */
  public function index(Request $request) {
    $path = $this->decoupledKit->checkPath($request);
    if ($path == '/') {
      $path = '<front>';
    }

    $mode = $this->decoupledKit->getMode(
      $request,
      $this->config('decoupled_kit_block.config'),
      ['link', 'data']
    );

    $visibled = [];
    $blocks = $this->getBlocks();
    if (!empty($blocks)) {
      $jsonapiExist = $this->moduleHandler()->moduleExists('jsonapi');

      foreach ($blocks as $id => $block) {
        $isVisible = $this->blockVisibleForPage($block, $path);
        if ($isVisible) {
          $uuid = $block->uuid();
          $label = $block->label();
          $plugin = $block->getPluginId();
          $region = $block->getRegion();
          $weight = $block->getWeight();

          // Generate data field.
          if ($mode == 'data') {
            // Set data array.
            $data = $this->getData($plugin);
          }
          else {
            // Set link to data array.
            $linkDataUrl = Url::fromRoute('decoupled_kit_block.data', [
              'plugin' => $plugin,
            ]);
            $data = $linkDataUrl->toString();
          }

          $block_data = [
            'region' => $region,
            'label' => $label,
            'link' => sprintf('/jsonapi/block/block/%s', $uuid),
            'uuid' => $uuid,
            'plugin' => $plugin,
            'weight' => $weight,
            'data' => $data,
            'settings' => $block->get('settings'),
          ];

          if ($jsonapiExist) {
            // Create JSON:API data link.
            [$plugin_type, $plugin_uuid] = explode(':', $plugin);
            if ($plugin_type == 'block_content' && !empty($plugin_uuid)) {
              $contentBlock = $this->getContentBlock($plugin_uuid);
              if (!empty($contentBlock)) {
                $bundle = $contentBlock->bundle();
                $block_data['bundle'] = $bundle;
                $block_data['link'] = sprintf('/jsonapi/%s/%s/%s', $plugin_type, $bundle, $plugin_uuid);
              }
            }
          }

          $visibled[$id] = $block_data;
        }
      }
    }

    $array = [
      'path' => $path,
      'mode' => $mode,
      'data' => $visibled,
    ];

    return new JsonResponse($array);
  }

  /**
   * Block objects list.
   *
   * @return array
   *   Blocks array.
   */
  protected function getBlocks() {
    $blocksManager = $this->entityTypeManager()->getStorage('block');
    $theme = $this->themeManager->getActiveTheme()->getName();
    $regions = system_region_list($theme, BlockRepositoryInterface::REGIONS_VISIBLE);

    $blocks = [];
    foreach ($regions as $key => $region) {
      $region_blocks = $blocksManager->loadByProperties([
        'theme' => $theme,
        'region' => $key,
      ]);

      if (!empty($region_blocks)) {
        $region_blocks = (array) $region_blocks;
        $blocks = array_merge($blocks, $region_blocks);
      }
    }

    return $blocks;
  }

  /**
   * Visibility check.
   *
   * @param object $block
   *   Block object.
   * @param string $input_path
   *   Path to the checking page.
   *
   * @return bool
   *   Is the block visibility?
   */
  protected function blockVisibleForPage($block, $input_path) {
    $visibility = $block->getVisibility();
    if (empty($visibility)) {
      return TRUE;
    }

    // Request path visibility.
    $requestPathVisibility = TRUE;
    $requestPath = $visibility['request_path'];
    if (!empty($requestPath)) {
      $negate = !empty($requestPath['negate']);
      if (empty($requestPath['pages'])) {
        $requestPathVisibility = !$negate;
      }
      else {
        $match = $this->pathMatcher->matchPath($input_path, $requestPath['pages']);
        $requestPathVisibility = $negate ? !$match : $match;
      }
    }

    // User roles visibility.
    $userRolesVisibility = TRUE;
    $userRoles = $visibility['user_role'];
    if (!empty($userRoles)) {
      $negate = !empty($userRoles['negate']);
      if (empty($userRoles['roles'])) {
        $userRolesVisibility = !$negate;
      }
      else {
        $userRolesVisibility = !empty(array_intersect($userRoles['roles'], $this->currentUser->getRoles()));
        if ($negate) {
          $userRolesVisibility = !$userRolesVisibility;
        }
      }
    }

    // Node type visibility.
    $nodeTypeVisibility = TRUE;
    $nodeType = $visibility['node_type'];
    if (!empty($nodeType)) {
      $negate = !empty($nodeType['negate']);
      if (empty($nodeType['bundles'])) {
        $nodeTypeVisibility = !$negate;
      }
      else {
        $entity = $this->decoupledKit->getEntityFromPath($input_path);
        if (!empty($entity) && $entity->getEntityTypeId() == 'node') {
          $nodeTypeVisibility = in_array($entity->getType(), $nodeType['bundles']);
          if ($negate) {
            $nodeTypeVisibility = !$nodeTypeVisibility;
          }
        }
      }
    }

    return $requestPathVisibility && $userRolesVisibility && $nodeTypeVisibility;
  }

  /**
   * JSON content of the block.
   *
   * @param string $plugin
   *   Block plugin.
   *
   * @return string
   *   JSON with block data.
   */
  public function getJsonData($plugin) {
    $data = $this->getData($plugin);
    return new JsonResponse([
      'plugin' => $plugin,
      'data' => $data,
    ]);
  }

  /**
   * Blocks data.
   *
   * @param string $plugin
   *   Blocks plugin.
   *
   * @return array
   *   Data for block plugin.
   */
  protected function getData($plugin) {
    $data = [];
    [$plugin_type, $plugin_uuid] = explode(':', $plugin);

    switch ($plugin_type) {
      case 'views_block':
        $data = $this->getViewsBlockData($plugin_uuid);
        break;

      case 'block_content':
        $data = $this->getContentBlockData($plugin_uuid);
        break;

      default:
        $data = $this->getCustomBlockData($plugin);
        break;
    }

    return $data;
  }

  /**
   * View content.
   *
   * @param string $id
   *   ID as view argument.
   *
   * @return array
   *   Views block data.
   */
  protected function getViewsBlockData($id) {
    [$view_name, $view_display] = explode('-', $id);
    if (empty($view_display)) {
      $view_display = 'default';
    }

    $view = views_embed_view($view_name, $view_display);
    if (!$view) {
      return [];
    }

    return $this->renderer->render($view, FALSE);
  }

  /**
   * Content block content.
   *
   * @param string $uuid
   *   Content block UUID.
   *
   * @return array
   *   Content block data.
   */
  protected function getContentBlockData($uuid) {
    $language = $this->languageManager()->getCurrentLanguage()->getId();
    $cid = sprintf('decoupled_kit_block:content_block:%s_%s', $uuid, $language);
    $cache = $this->cache()->get($cid);
    if ($cache) {
      return $cache->data;
    }

    $data = [];
    $contentBlock = $this->getContentBlock($uuid);
    if ($contentBlock) {
      $data = $this->entityTypeManager()
        ->getViewBuilder('block_content')
        ->view($contentBlock);

      // Save to cache.
      $this->cache()->set($cid, $data, CacheBackendInterface::CACHE_PERMANENT, $data['#cache']['tags']);
    }

    return $data;
  }

  /**
   * Custom block entity.
   *
   * @param string $id
   *   Custom block plugin id.
   *
   * @return string
   *   Custom block render.
   */
  protected function getCustomBlockData($id) {
    $config = [];
    $pluginBlock = $this->pluginManagerBlock->createInstance($id, $config);
    if ($pluginBlock) {
      $build = $pluginBlock->build();
      $render = render($build);
      return $render;
    }

    return '';
  }

  /**
   * Content block entity.
   *
   * @param string $uuid
   *   Content block UUID.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Content block entity.
   */
  private function getContentBlock($uuid) {
    /** @var array|string $contentBlock */
    $contentBlock = $this->entityTypeManager()
      ->getStorage('block_content')
      ->loadByProperties(['uuid' => $uuid]);

    if (!empty($contentBlock)) {
      if (is_array($contentBlock)) {
        $contentBlock = reset($contentBlock);
      }
      return $contentBlock;
    }

    return NULL;
  }

}
