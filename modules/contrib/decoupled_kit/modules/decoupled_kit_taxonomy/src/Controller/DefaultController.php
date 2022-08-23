<?php

namespace Drupal\decoupled_kit_taxonomy\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->decoupledKit = $container->get('decoupled_kit');
    return $instance;
  }

  /**
   * Get taxonomy tree.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   * @param string $id
   *   Taxonomy id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return taxonomy data.
   */
  public function index(Request $request, $id) {
    $mode = $this->decoupledKit->getMode(
      $request,
      $this->config('decoupled_kit_taxonomy.config'),
      ['plain', 'tree']
    );
    $language = $this->languageManager()->getCurrentLanguage()->getId();

    // Get from cache.
    $cid = sprintf('decoupled_kit_taxonomy:%s_%s_%s', $id, $mode, $language);
    $cache = $this->cache()->get($cid);
    if ($cache) {
      return new JsonResponse($cache->data);
    }

    $tree = $this->entityTypeManager()->getStorage('taxonomy_term')->loadTree($id);
    if (empty($tree)) {
      throw new NotFoundHttpException($this->t('Cannot load taxonomy tree for @id', ['@id' => $id]));
    }

    // Make taxonomy tree.
    if ($mode == 'tree') {
      $tree = $this->makeTree($tree);
    }

    $array = [
      'id' => $id,
      'mode' => $mode,
      'data' => $tree,
    ];

    // Save to cache.
    $this->cache()->set($cid, $array, CacheBackendInterface::CACHE_PERMANENT, ['taxonomy_term_list']);

    return new JsonResponse($array);
  }

  /**
   * Make taxonomy tree.
   *
   * @param array $terms
   *   Plain taxonomy.
   *
   * @return array
   *   Return taxonomy tree.
   */
  protected function makeTree(array $terms) {
    // Parents array.
    $tax_parents = [];
    foreach ($terms as $key => $value) {
      $tid = intval($value->tid);
      $index = $key . '_' . $tid;
      $term = $terms[$key];
      if ($term) {
        $parents = $term->parents;
        if (!empty($parents)) {
          $parent = intval($parents[0]);
          $tax_parents[$index][] = $parent;
          foreach ($tax_parents as $key2 => $value2) {
            [, $tid2] = explode('_', $key2);
            if ($tid2 == $parent) {
              $tax_parents[$index] = array_merge($tax_parents[$index], $value2);
              break;
            }
          }
        }
      }
    }

    // Taxonomy index.
    $tax_index = [];
    foreach ($tax_parents as $key => $value) {
      [, $tid] = explode('_', $key);
      $tax_index[$tid] = $key;
    }

    // Make tree.
    $tree = [];
    $i = 0;
    foreach ($terms as $key => $value) {
      $tid = intval($value->tid);
      $weight = intval($value->weight);
      $depth = intval($value->depth);

      // Parents.
      $parent = NULL;
      $parents = $value->parents;
      if (!empty($parents)) {
        if (isset($parents[0]) && $parents[0] > 0) {
          $parent = intval($parents[0]);
        }
      }

      $index = $i . '_' . $tid;
      $parents_all = array_reverse($tax_parents[$index]);

      $arr = [
        'name' => $value->name,
        'weight' => $weight,
        'depth' => $depth,
        'tid' => $tid,
        'parent' => $parent,
        'parents_all' => $parents_all,
      ];

      if (!$parent) {
        // Level 0.
        $tree[$index] = $arr;
      }
      else {
        // Make deep level.
        $deep_index = [];
        foreach ($parents_all as $value) {
          if ($value > 0) {
            $parent_index = $tax_index[$value];
            $deep_index[] = $parent_index;
            $deep_index[] = 'childs';
          }
        }

        // Set value fpr key.
        $reference = &$tree;
        foreach ($deep_index as $key) {
          if (!array_key_exists($key, $reference)) {
            $reference[$key] = [];
          }
          $reference = &$reference[$key];
        }
        $reference[$index] = $arr;
        unset($reference);
      }

      $i++;
    }

    return $tree;
  }

}
