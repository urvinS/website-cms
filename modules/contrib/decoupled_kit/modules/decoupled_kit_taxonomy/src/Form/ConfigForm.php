<?php

namespace Drupal\decoupled_kit_taxonomy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigForm.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'decoupled_kit_taxonomy.config',
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
    $config = $this->config('decoupled_kit_taxonomy.config');
    $form['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Taxonomy output'),
      '#description' => $this->t('Configure default mode of taxonomy output: plain or tree. May be set directly by ?mode=plain or ?mode=tree.'),
      '#options' => [
        'plain' => $this->t('Plain: output as a plain list (default)'),
        'tree' => $this->t('Tree: output as a tree'),
      ],
      '#default_value' => $config->get('mode'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('decoupled_kit_taxonomy.config')
      ->set('mode', $form_state->getValue('mode'))
      ->save();
  }

}
