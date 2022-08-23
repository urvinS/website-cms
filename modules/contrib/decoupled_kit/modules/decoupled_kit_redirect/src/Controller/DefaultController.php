<?php

namespace Drupal\decoupled_kit_redirect\Controller;

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->decoupledKit = $container->get('decoupled_kit');
    return $instance;
  }

  /**
   * Get redirect data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return redirect data.
   */
  public function index(Request $request) {
    $path = $this->decoupledKit->checkPath($request, FALSE);
    $mode = $this->decoupledKit->getMode(
      $request,
      $this->config('decoupled_kit_redirect.config'),
      ['simple', 'full', 'final']
    );

    $array = [];
    $redirect = $this->getRedirectStack($path, $mode);
    if (!empty($redirect)) {
      $array = [
        'path' => $path,
        'mode' => $mode,
        'data' => $redirect,
      ];
    }

    return new JsonResponse($array);
  }

  /**
   * Get redirect stack.
   *
   * @param string $path
   *   Input path.
   * @param bool $mode
   *   Output mode.
   *
   * @return array|bool
   *   Return redirect stack array or NULL.
   */
  public function getRedirectStack($path, $mode) {
    $redirectsTrace = [];

    $redirectStorage = $this->entityTypeManager()->getStorage('redirect');
    $destination = parse_url($path, PHP_URL_PATH);
    $originalQueryString = parse_url($path, PHP_URL_QUERY);

    $tracedUrls = [];
    $redirect = NULL;
    while (TRUE) {
      $results = $redirectStorage->getQuery()
        ->condition('redirect_source.path', ltrim($path, '/'))
        ->execute();
      $rid = reset($results);
      if (!$rid) {
        break;
      }

      $redirect = $redirectStorage->load($rid);
      $uri = $redirect->get('redirect_redirect')->uri;
      $url = Url::fromUri($uri)->toString(TRUE);

      $fromUrl = $this->makeRedirectUrl($destination, $originalQueryString);
      $toUrl = $this->makeRedirectUrl($this->decoupledKit->canonicalPath($url->getGeneratedUrl()), $originalQueryString);
      if ($fromUrl == $toUrl) {
        break;
      }

      $redirectsTrace[] = [
        'from' => $fromUrl,
        'to' => $toUrl,
        'status' => $redirect->getStatusCode(),
      ];

      // Return for simple.
      if ($mode == 'simple') {
        return reset($redirectsTrace);
      }

      $destination = $this->decoupledKit->canonicalPath($url->getGeneratedUrl());
      if (in_array($destination, $tracedUrls)) {
        break;
      }
      $tracedUrls[] = $destination;
    }

    if (empty($redirectsTrace)) {
      return NULL;
    }

    // Return last redirect.
    if ($mode == 'final') {
      return end($redirectsTrace);
    }

    // Return full redirects trace.
    return $redirectsTrace;
  }

  /**
   * Generates URL for the redirect, based on redirect module configurations.
   *
   * @param string $path
   *   URL to redirect to.
   * @param string $query
   *   Original query string on the requested path.
   *
   * @return string
   *   Redirect URL to use.
   */
  private function makeRedirectUrl($path, $query) {
    return $query && $this->config('redirect.settings')
      ->get('passthrough_querystring')
      ? "{$path}?{$query}"
      : $path;
  }

}
