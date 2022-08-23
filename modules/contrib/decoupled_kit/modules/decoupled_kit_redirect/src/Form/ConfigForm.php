<?php

namespace Drupal\decoupled_kit_redirect\Form;

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
      'decoupled_kit_redirect.config',
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
    $config = $this->config('decoupled_kit_redirect.config');
    $form['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Output mode'),
      '#description' => $this->t('Configure default mode of data generation: simple, full or final. May be set directly by ?mode=link or ?mode=final'),
      '#options' => [
        'simple' => $this->t('Simple: just a first redirect'),
        'full' => $this->t('Full: full redirect trace'),
        'final' => $this->t('Final: final redirect (default)'),
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

    $this->config('decoupled_kit_redirect.config')
      ->set('mode', $form_state->getValue('mode'))
      ->save();
  }

}
