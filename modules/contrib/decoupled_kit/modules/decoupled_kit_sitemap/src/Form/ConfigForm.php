<?php

namespace Drupal\decoupled_kit_sitemap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigForm.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
  protected function getEditableConfigNames() {
    return [
      'decoupled_kit_sitemap.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('decoupled_kit_sitemap.config');
    $items = $config->get('items');
    if (!empty($items)) {
      $items = json_decode($items, TRUE);
    }
    if (empty($items)) {
      $items = [];
    }

    if ($this->moduleHandler->moduleExists('decoupled_kit_menu')) {
      $menus = $this->entityTypeManager->getStorage('menu')->loadMultiple();
      if ($menus) {
        $form['menus'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Menu'),
        ];

        foreach ($menus as $key => $value) {
          $default_value = isset($items['menu'][$key]);

          $form['menus']['menu__' . $key] = [
            '#type' => 'checkbox',
            '#title' => $value->label(),
            '#default_value' => $default_value,
          ];
        }
      }
    }

    if ($this->moduleHandler->moduleExists('decoupled_kit_taxonomy')) {
      $vocabs = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();
      if ($vocabs) {
        $form['vocabularies'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Vocabularies'),
        ];

        foreach ($vocabs as $key => $value) {
          $default_value = isset($items['vocabulary'][$key]);

          $form['vocabularies']['vocabulary__' . $key] = [
            '#type' => 'checkbox',
            '#title' => $value->label(),
            '#default_value' => $default_value,
          ];
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $items = [];
    foreach ($form_state->getGroups() as $group => $value) {
      if (in_array($group, ['menus', 'vocabularies'])) {
        continue;
      }
      if (!$form_state->getValue($group)) {
        continue;
      }

      [$entity, $item] = explode('__', $group);
      $items[$entity][$item] = $item;
    }

    $this->config('decoupled_kit_sitemap.config')
      ->set('items', json_encode($items))
      ->save();
  }

}
