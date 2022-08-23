<?php

namespace Drupal\decoupled_kit_breadcrumb\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Config Form.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'decoupled_kit_breadcrumb.config',
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
    $config = $this->config('decoupled_kit_breadcrumb.config');
    $form['need_front'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include frontpage to breadcrumbs'),
      '#description' => $this->t('Include frontpage to breadcrumbs'),
      '#default_value' => $config->get('need_front'),
    ];
    $form['need_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include title to breadcrumbs'),
      '#description' => $this->t('Include title to breadcrumbs'),
      '#default_value' => $config->get('need_title'),
    ];
    $form['path_patterns'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Path patterns'),
      '#description' => $this->t('Use it if have not automatic breadcrumbs. Pattern: Bundle|Link|Title. Bundle is an alias part. For example: "news|news.html|Latest news" for "/news/somenews.html" page and news.html as news list page.'),
      '#default_value' => $config->get('path_patterns'),
      '#rows' => 10,
    ];
    $form['use_bundle_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use bundle name'),
      '#description' => $this->t('Use bundle name if no breadcrumbs.
        For example: news page "/some_news_page.html" will be have "News"
        breadcrumbs as a name of the node bundle.'),
      '#default_value' => $config->get('use_bundle_name'),
    ];
    $form['need_url_validate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Validate urls'),
      '#description' => $this->t('Include validated urls only.'),
      '#default_value' => $config->get('need_url_validate'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('decoupled_kit_breadcrumb.config')
      ->set('need_front', $form_state->getValue('need_front'))
      ->set('need_title', $form_state->getValue('need_title'))
      ->set('path_patterns', $form_state->getValue('path_patterns'))
      ->set('need_url_validate', $form_state->getValue('need_url_validate'))
      ->save();
  }

}
