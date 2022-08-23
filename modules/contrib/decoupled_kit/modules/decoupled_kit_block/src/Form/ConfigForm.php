<?php

namespace Drupal\decoupled_kit_block\Form;

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
      'decoupled_kit_block.config',
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
    $config = $this->config('decoupled_kit_block.config');
    $form['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Data mode'),
      '#description' => $this->t('Configure default mode of data generation: link or data. May be set directly by ?mode=link or ?mode=data.'),
      '#options' => [
        'link' => $this->t('Link: generate link to data only (default)'),
        'data' => $this->t('Data: generate data'),
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

    $this->config('decoupled_kit_block.config')
      ->set('mode', $form_state->getValue('mode'))
      ->save();
  }

}
