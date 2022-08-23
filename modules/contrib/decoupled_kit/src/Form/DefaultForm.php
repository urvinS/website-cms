<?php

namespace Drupal\decoupled_kit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Class DefaultForm.
 */
class DefaultForm extends FormBase {

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current page path for dashboard.
   *
   * @var string
   */
  protected $path;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'default_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Current page for dashboard.
    $this->path = $this->getRequest()->query->get('path') ?? '/';

    $form['path'] = [
      '#title' => $this->t('Path for dashboard links'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $this->path,
      '#weight' => -2,
    ];
    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => -1,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Change page'),
    ];

    if ($this->moduleHandler->moduleExists('decoupled_kit_object')) {
      $form['object_table'] = $this->getTable(
        $this->t('Object'),
        'decoupled_kit_object.index',
        TRUE
      );
    }

    if ($this->moduleHandler->moduleExists('decoupled_kit_router')) {
      $form['router_table'] = $this->getTable(
        $this->t('Routers'),
        'entity.decoupled_kit_router.collection'
      );
    }

    if ($this->moduleHandler->moduleExists('decoupled_kit_block')) {
      $form['block_table'] = $this->getTable(
        $this->t('Blocks'),
        'decoupled_kit_block.index',
        TRUE,
        ['link', 'data']
      );
    }

    if ($this->moduleHandler->moduleExists('decoupled_kit_menu')) {
      $menus = $this->entityTypeManager->getStorage('menu')->loadMultiple();
      if ($menus) {
        $names = [];
        foreach ($menus as $key => $value) {
          $names[$key] = $value->label();
        }

        $form['menu_table'] = $this->getTable(
          $this->t('Menu'),
          'decoupled_kit_menu.index',
          FALSE,
          [],
          $names
        );
      }
    }

    if ($this->moduleHandler->moduleExists('decoupled_kit_taxonomy')) {
      $vocabs = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();
      if ($vocabs) {
        $names = [];
        foreach ($vocabs as $key => $value) {
          $names[$key] = $value->label();
        }

        $form['taxonomy_table'] = $this->getTable(
          $this->t('Vocabularies'),
          'decoupled_kit_taxonomy.index',
          FALSE,
          ['plain', 'tree'],
          $names
        );
      }
    }

    if ($this->moduleHandler->moduleExists('decoupled_kit_sitemap')) {
      $form['sitemap_table'] = $this->getTable(
        $this->t('Sitemap'),
        'decoupled_kit_sitemap.index'
      );
    }

    if ($this->moduleHandler->moduleExists('decoupled_kit_breadcrumb')) {
      $form['breadcrumb_table'] = $this->getTable(
        $this->t('Breadcrumbs'),
        'decoupled_kit_breadcrumb.index',
        TRUE
      );
    }

    if ($this->moduleHandler->moduleExists('decoupled_kit_metatag')) {
      $form['metatag_table'] = $this->getTable(
        $this->t('Metatags'),
        'decoupled_kit_metatag.index',
        TRUE
      );
    }

    if ($this->moduleHandler->moduleExists('decoupled_kit_webform')) {
      $form['webform_table'] = $this->getTable(
        $this->t('Webform'),
        'decoupled_kit_webform.index',
        FALSE,
        [],
        [1 => $this->t('Webform submission id')]
      );
    }

    if ($this->moduleHandler->moduleExists('decoupled_kit_redirect')) {
      $form['redirect_table'] = $this->getTable(
        $this->t('Redirect'),
        'decoupled_kit_redirect.index',
        TRUE,
        ['simple', 'full', 'final']
      );
    }

    return $form;
  }

  /**
   * Get form table.
   *
   * @param string $caption
   *   Caption for the table.
   * @param string $route
   *   Base route from module.
   * @param bool $needPathOption
   *   Need add ?path.
   * @param array $options
   *   Query options.
   * @param array $arguments
   *   Url arguments.
   *
   * @return array
   *   Table array for form.
   */
  protected function getTable($caption, $route, $needPathOption = FALSE, array $options = [], array $arguments = []) {
    $header = [$this->t('Link')];
    $rows = [];

    $query = [];
    if ($needPathOption) {
      $query['path'] = $this->path;
    }

    if (!empty($arguments) && !empty($options)) {
      foreach ($arguments as $id => $name) {
        $header = [$this->t('Link')];

        // First column.
        $url = Url::fromRoute($route, ['id' => $id]);
        $link = Link::fromTextAndUrl($name, $url);
        $data = [$link->toString()];

        foreach ($options as $option) {
          // Add columns for each option.
          $header[] = $this->t('Url: @mode', [
            '@mode' => $option,
          ]);

          $url_options = array_merge($query, ['mode' => $option]);
          $url = Url::fromRoute($route, ['id' => $id], ['query' => $url_options]);
          $label = $this->t('@argument: ?mode=@mode', [
            '@argument' => $name,
            '@mode' => $option,
          ]);
          $link = Link::fromTextAndUrl($label, $url);
          $data[] = $link->toString();
        }
        $rows[] = ['data' => $data];
      }
    }
    else {
      if (!empty($arguments)) {
        foreach ($arguments as $id => $name) {
          $url = Url::fromRoute($route, ['id' => $id]);
          $link = Link::fromTextAndUrl($name, $url);
          $rows[] = ['data' => [$link->toString()]];
        }
      }
      else {
        $data = [];

        // First column.
        $url = Url::fromRoute($route, [], ['query' => $query]);
        $link = Link::fromTextAndUrl($caption, $url);
        $data[] = $link->toString();

        if (!empty($options)) {
          // Add columns for each option.
          foreach ($options as $option) {
            $header[] = $this->t('Url: @mode', [
              '@mode' => $option,
            ]);

            $url_options = array_merge($query, ['mode' => $option]);
            $url = Url::fromRoute($route, [], ['query' => $url_options]);
            $label = $this->t('@caption: ?mode=@mode', [
              '@caption' => $caption,
              '@mode' => $option,
            ]);
            $link = Link::fromTextAndUrl($label, $url);
            $data[] = $link->toString();
          }
        }
        $rows[] = ['data' => $data];
      }
    }

    return [
      '#type' => 'table',
      '#caption' => $caption,
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $path = trim($form_state->getValue('path'));
    $path = sprintf('/%s', ltrim($path, '/'));
    $query['path'] = $path;
    $form_state->setRedirect('decoupled_kit.main', [], ['query' => $query]);
  }

}
