<?php
namespace Drupal\restuiextention\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure basic settings for REST UI Extension module.
 *
 * @internal
 */
class BasicSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'restuiextention_basic_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'restuiextention.basic.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('restuiextention.basic.settings');
    /* Basic configuration */
    $form['basic_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Log configuration'),
      '#open' => TRUE,
    ];
	
    $form['basic_settings']['enable_log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Log'),
      '#default_value' => $config->get('enable_log'),
    ];
	
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('restuiextention.basic.settings')
      ->set('enable_log', $form_state->getValue('enable_log'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
