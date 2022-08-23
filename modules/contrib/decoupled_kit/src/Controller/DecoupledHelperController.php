<?php

namespace Drupal\decoupled_kit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\ContentEntityType;

/**
 * Class DecoupledHelperController.
 */
class DecoupledHelperController extends ControllerBase {

  /**
   * Check request path.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   User request.
   * @param bool $needCanonicalUrl
   *   Need call canonicalPath().
   *
   * @return string
   *   Checked path or exception if empty.
   */
  public function checkPath(Request $request, $needCanonicalUrl = TRUE) {
    $path = $request->query->get('path');
    if (empty($path)) {
      throw new NotFoundHttpException('Unable to work with empty path. Please send a ?path query string parameter with your request.');
    }

    if ($needCanonicalUrl) {
      $path = $this->canonicalPath($path);
    }
    return $path;
  }

  /**
   * Canonical path.
   *
   * @param string $path
   *   Input path.
   * @param bool $url_path_only
   *   Only path part of url.
   *
   * @return string
   *   Canonical path.
   */
  public function canonicalPath($path, $url_path_only = TRUE) {
    $path = mb_strtolower(trim($path));
    if ($url_path_only) {
      $path = parse_url($path, PHP_URL_PATH);
    }
    return sprintf('/%s', ltrim($path, '/'));
  }

  /**
   * Get entity from path.
   *
   * @param string $path
   *   Path.
   * @param bool $checkAllow
   *   Need check entity for allow.
   *
   * @return object|bool
   *   Entity object or null.
   */
  public function getEntityFromPath($path, $checkAllow = TRUE) {
    if (empty($path)) {
      return NULL;
    }

    try {
      $params = Url::fromUri("internal:" . $path)->getRouteParameters();
    }
    catch (\Exception $e) {
      return NULL;
    }
    $entityType = key($params);
    if (empty($entityType)) {
      return NULL;
    }

    $id = $params[$entityType];
    $entity = $this->entityTypeManager()->getStorage($entityType)->load($id);

    // Check translation.
    if ($entity) {
      $language = $this->languageManager()->getCurrentLanguage()->getId();
      $entity = $entity->getTranslation($language);
    }

    // Check allows.
    if ($entity && $checkAllow) {
      $allowEntity = $this->allowEntity($entity);
      if (!$allowEntity) {
        return FALSE;
      }
    }

    return $entity;
  }

  /**
   * Check allowed entity.
   *
   * @param object $entity
   *   Entity for check.
   *
   * @return bool
   *   Is allowed entity.
   */
  public function allowEntity($entity) {
    if ($entity->getEntityType() instanceof ContentEntityType) {
      $can_view = $entity->access('view', NULL, TRUE);
      if (!$can_view->isAllowed()) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Get mode.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   User request.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Config object.
   * @param array $modeList
   *   Mode list.
   *
   * @return string
   *   Mode value.
   */
  public function getMode(Request $request, ImmutableConfig $config, array $modeList) {
    $mode_from_config = $config->get('mode');
    $mode = $request->query->get('mode') ?? $mode_from_config;
    if (!in_array($mode, $modeList)) {
      $mode = $mode_from_config;
    }
    return $mode;
  }

}
