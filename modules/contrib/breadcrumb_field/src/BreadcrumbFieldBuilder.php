<?php

namespace Drupal\breadcrumb_field;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuLinkManager;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\system\PathBasedBreadcrumbBuilder;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * Class BreadcrumbFieldBuilder
 *
 * @package Drupal\breadcrumb_field
 */
class BreadcrumbFieldBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * The router request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $context;

  /**
   * The menu link access service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The dynamic router service.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected $router;

  /**
   * The dynamic router service.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * Breadcrumb config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Site config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $siteConfig;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * @var FieldItemListInterface $items
   */
  protected $fieldLinks;

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManager
   */
  protected $menuLinkManager;

  /**
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Constructs the PathBasedBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Routing\RequestContext $context
   *   The router request context.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The menu link access service.
   * @param \Symfony\Component\Routing\Matcher\RequestMatcherInterface $router
   *   The dynamic router service.
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   The inbound path processor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user object.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   * @param \Drupal\Core\Menu\MenuLinkManager $menu_link_manager
   */
  public function __construct(RequestContext $context, AccessManagerInterface $access_manager, RequestMatcherInterface $router, InboundPathProcessorInterface $path_processor, ConfigFactoryInterface $config_factory, TitleResolverInterface $title_resolver, AccountInterface $current_user, CurrentPathStack $current_path, MenuLinkManager $menu_link_manager) {
    $this->context = $context;
    $this->accessManager = $access_manager;
    $this->router = $router;
    $this->pathProcessor = $path_processor;
    $this->config = $config_factory->get('breadcrumb_field.settings');
    $this->siteConfig = $config_factory->get('system.site');
    $this->titleResolver = $title_resolver;
    $this->currentUser = $current_user;
    $this->currentPath = $current_path;
    $this->menuLinkManager = $menu_link_manager;
  }

  /**
   * Whether this breadcrumb builder should be used to build the breadcrumb.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return bool
   *   TRUE if this builder should be used or FALSE to let other builders
   *   decide.
   */
  public function applies(RouteMatchInterface $route_match) {
    $parameters = $route_match->getParameters();
    foreach ($parameters as $parameter) {
      // Add support for nodes and Terms only.
      if (($parameter instanceof NodeInterface || $parameter instanceof TermInterface) && isset($this->fieldLinks) && !$this->fieldLinks->isEmpty()) {
        $entity = $this->fieldLinks->getEntity();
        // It applies only on page with values from breadcrumb field.
        return $entity->id() === $parameter->id();
      }
    }
    return FALSE;
  }

  /**
   * @return bool|\Drupal\node\NodeInterface
   */
  public function getCurrentNode() {
    $node = \Drupal::service('current_route_match')->getParameter('node');
    return $node instanceof NodeInterface ? $node : FALSE;
  }

  /**
   * @return bool|\Drupal\node\NodeInterface
   */
  public function getCurrentTerm() {
    $term = \Drupal::service('current_route_match')->getParameter('taxonomy_term');
    return $term instanceof TermInterface ? $term : FALSE;
  }

  /**
   * Builds the breadcrumb.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return \Drupal\Core\Breadcrumb\Breadcrumb
   *   A breadcrumb.
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $exclude = [];
    $links = [];
    $curr_lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    // General path-based breadcrumbs. Use the actual request path, prior to
    // resolving path aliases, so the breadcrumb can be defined by simply
    // creating a hierarchy of path aliases.
    $path = trim($this->context->getPathInfo(), '/');
    $path = urldecode($path);
    $path_elements = explode('/', $path);
    $front = $this->siteConfig->get('page.front');

    // Give the option to keep the breadcrumb on the front page.
    $keep_front = !empty($this->config->get(BreadcrumbFieldConstants::HOME_SEGMENT_TITLE))
      && $this->config->get(BreadcrumbFieldConstants::HOME_SEGMENT_KEEP);
    $exclude[$front] = !$keep_front;
    $exclude[''] = !$keep_front;
    $exclude['/user'] = TRUE;

    // Because this breadcrumb builder is path and config based, vary cache
    // by the 'url.path' cache context and config changes.
    $breadcrumb->addCacheContexts(['url.path', 'languages', 'url.query_args']);
    $breadcrumb->addCacheableDependency($this->config);

    // Remove all path elements for not included title because we're using field links.
    if (!$this->config->get(BreadcrumbFieldConstants::INCLUDE_TITLE_SEGMENT)) {
      unset($path_elements);
    }

    // Add the home link, if desired.
    if ($this->config->get(BreadcrumbFieldConstants::INCLUDE_HOME_SEGMENT)) {
      if ($path && '/' . $path != $front && $path != $curr_lang) {
        $links[] = Link::createFromRoute($this->config->get(BreadcrumbFieldConstants::HOME_SEGMENT_TITLE), '<front>');
      }
    }

    // If breadcrumb field has value
    if (isset($this->fieldLinks) && !$this->fieldLinks->isEmpty()) {
      foreach ($this->fieldLinks->getValue() as $field_value) {
        try {
          $params = Url::fromUri($field_value['uri'])->getRouteParameters();
        }
        catch (\UnexpectedValueException $exception) {
          // Support only internal paths.
          \Drupal::logger('breadcrumb_field')
            ->notice('Breadcrumb field could not be applied for external URL for @path', [
              '@path' => $route_match->getRouteObject()->getPath()]);
          break;
        }
        $entity_type = key($params);
        $node_id = current($params);
        $links[] = Link::createFromRoute($field_value['title'], 'entity.node.canonical', [$entity_type => $node_id]);
      }
      // Add cache tags from current node page.
      $node = $this->getCurrentNode();
      if ($node) {
        $breadcrumb->addCacheableDependency($node);
      }
      $term = $this->getCurrentTerm();
      if ($term) {
        $breadcrumb->addCacheableDependency($term);
      }
    }
    if ($this->config->get(BreadcrumbFieldConstants::INCLUDE_TITLE_SEGMENT)) {
      // Get only last one path element.
      $path_elements = [array_pop($path_elements)];
      $check_path = '/' . implode('/', $path_elements);
      // Copy the path elements for up-casting.
      $route_request = $this->getRequestForPath($check_path, $exclude);
      if ($route_request) {
        $route_match = RouteMatch::createFromRequest($route_request);
        $access = $this->accessManager->check($route_match, $this->currentUser, NULL, TRUE);
        $breadcrumb = $breadcrumb->addCacheableDependency($access);
        // The set of breadcrumb links depends on the access result, so merge
        // the access result's cacheability metadata.
        if ($access->isAllowed()) {
          if ($this->config->get(BreadcrumbFieldConstants::TITLE_FROM_PAGE_WHEN_AVAILABLE)) {
            $title = $this->getTitleString($route_request, $route_match);
          }
          if (!isset($title)) {
            // Try resolve the menu title from the route.
            $route_name = $route_match->getRouteName();
            $route_parameters = $route_match->getRawParameters()->all();
            $menu_links = $this->menuLinkManager->loadLinksByRoute($route_name, $route_parameters);

            if (!empty($menu_links)) {
              $menu_link = reset($menu_links);
              $title = $menu_link->getTitle();
            }
          }
          // Fallback to using the raw path component as the title if the
          // route is missing a _title or _title_callback attribute.
          if (!isset($title)) {
            $title = str_replace([
              '-',
              '_',
            ], ' ', Unicode::ucfirst(end($path_elements)));
          }
          // Add a linked breadcrumb unless it's the current page.
          if (!$this->config->get(BreadcrumbFieldConstants::TITLE_SEGMENT_AS_LINK)) {
            $links[] = Link::createFromRoute($title, '<none>');
          }
          else {
            $url = Url::fromRouteMatch($route_match);
            $links[] = new Link($title, $url);
          }
          unset($title);
        }
      }
      elseif (empty($exclude[implode('/', $path_elements)])) {
        $title = str_replace([
          '-',
          '_',
        ], ' ', Unicode::ucfirst(end($path_elements)));
        $links[] = Link::createFromRoute($title, '<none>');
        unset($title);
      }
    }

    if ($this->config->get(BreadcrumbFieldConstants::HIDE_SINGLE_HOME_ITEM) && count($links) === 1) {
      return $breadcrumb->setLinks([]);
    }
    return $breadcrumb->setLinks($links);
  }

  /**
   * Setter for fields items.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $fieldItemList
   *
   * @return $this
   */
  public function setFieldLinks(FieldItemListInterface $fieldItemList) {
    $this->fieldLinks = $fieldItemList;
    return $this;
  }

  /**
   * Get string title for route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $route_request
   *   A request object.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match *
   *
   * @return string | FALSE
   *   Either the current title string or FALSE if unable to determine it.
   */
  public function getTitleString(Request $route_request, RouteMatchInterface $route_match) {
    $title = $this->titleResolver->getTitle($route_request, $route_match->getRouteObject());
    // Get string from title. Different routes return different objects.
    // Many routes return a translatable markup object.
    if ($title instanceof TranslatableMarkup) {
      $title = $title->render();
    }
    elseif ($title instanceof FormattableMarkup) {
      $title = (string) $title;
    }

    // Other paths, such as admin/structure/menu/manage/main, will
    // return a render array suitable to render using core's XSS filter.
    elseif (is_array($title) && array_key_exists('#markup', $title)) {
      // If this render array has #allowed tags use that instead of default.
      $tags = array_key_exists('#allowed_tags', $title) ? $title['#allowed_tags'] : NULL;
      $title = Xss::filter($title['#markup'], $tags);
    }

    // If a route declares the title in an unexpected way log and return FALSE.
    if (!is_string($title)) {
      \Drupal::logger('breadcrumb_field')
        ->notice('Breadcrumb field could not determine the title to use for @path', [
          '@path' => $route_match->getRouteObject()
            ->getPath(),
        ]);
      return FALSE;
    }
    return $title;
  }

  /**
   * Matches a path in the router.
   *
   * @param string $path
   *   The request path with a leading slash.
   * @param array $exclude
   *   An array of paths or system paths to skip.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   A populated request object or NULL if the path couldn't be matched.
   */
  protected function getRequestForPath($path, array $exclude) {
    if (!empty($exclude[$path])) {
      return NULL;
    }
    // @todo Use the RequestHelper once https://www.drupal.org/node/2090293 is
    //   fixed.
    $request = Request::create($path);
    // Performance optimization: set a short accept header to reduce overhead in
    // AcceptHeaderMatcher when matching the request.
    $request->headers->set('Accept', 'text/html');
    // Find the system path by resolving aliases, language prefix, etc.
    $processed = $this->pathProcessor->processInbound($path, $request);
    if (empty($processed) || !empty($exclude[$processed])) {
      // This resolves to the front page, which we already add.
      return NULL;
    }
    $this->currentPath->setPath($processed, $request);
    // Attempt to match this path to provide a fully built request.
    try {
      $request->attributes->add($this->router->matchRequest($request));
      return $request;
    }
    catch (ParamNotConvertedException $e) {
      return NULL;
    }
    catch (ResourceNotFoundException $e) {
      return NULL;
    }
    catch (MethodNotAllowedException $e) {
      return NULL;
    }
    catch (AccessDeniedHttpException $e) {
      return NULL;
    }
  }
}
