<?php

namespace Drupal\breadcrumb_field\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\breadcrumb_field\BreadcrumbFieldConstants;

/**
 * Class BreadcrumbFieldConfigForm.
 */
class BreadcrumbFieldConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'breadcrumb_field.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'breadcrumb_field_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('breadcrumb_field.settings');
    // Fieldset for grouping general settings fields.
    $fieldset_general = [
      '#type' => 'fieldset',
      '#title' => $this->t('General settings'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $fieldset_general[BreadcrumbFieldConstants::INCLUDE_HOME_SEGMENT] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include the front page as a segment in the breadcrumb'),
      '#description' => $this->t('Include the front page as the first segment in the breadcrumb.'),
      '#default_value' => $config->get(BreadcrumbFieldConstants::INCLUDE_HOME_SEGMENT),
    ];

    $fieldset_general[BreadcrumbFieldConstants::HOME_SEGMENT_TITLE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title for the front page segment in the breadcrumb'),
      '#description' => $this->t('Text to be displayed as the front page segment.'),
      '#default_value' => $config->get(BreadcrumbFieldConstants::HOME_SEGMENT_TITLE),
    ];

    $fieldset_general[BreadcrumbFieldConstants::HOME_SEGMENT_KEEP] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display the front page segment on the front page'),
      '#description' => $this->t('If checked, the Home segment will be displayed on the front page.'),
      '#default_value' => $config->get(BreadcrumbFieldConstants::HOME_SEGMENT_KEEP),
      '#states' => [
        'visible' => [
          ':input[name="' . BreadcrumbFieldConstants::HOME_SEGMENT_TITLE . '"]' => ['empty' => FALSE],
        ],
      ],
    ];

    $fieldset_general[BreadcrumbFieldConstants::INCLUDE_TITLE_SEGMENT] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include the current page as a segment in the breadcrumb'),
      '#description' => $this->t('Include the current page as the last segment in the breadcrumb.'),
      '#default_value' => $config->get(BreadcrumbFieldConstants::INCLUDE_TITLE_SEGMENT),
    ];

    $fieldset_general[BreadcrumbFieldConstants::TITLE_FROM_PAGE_WHEN_AVAILABLE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use the real page title when available'),
      '#description' => $this->t('Use the real page title when it is available instead of always deducing it from the URL.'),
      '#default_value' => $config->get(BreadcrumbFieldConstants::TITLE_FROM_PAGE_WHEN_AVAILABLE),
    ];

    $fieldset_general[BreadcrumbFieldConstants::TITLE_SEGMENT_AS_LINK] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make the page title segment a link'),
      '#description' => $this->t('Prints the page title segment as a link.'),
      '#default_value' => $config->get(BreadcrumbFieldConstants::TITLE_SEGMENT_AS_LINK),
    ];

    $fieldset_general[BreadcrumbFieldConstants::HIDE_SINGLE_HOME_ITEM] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Hide link to home page if it's the only breadcrumb item"),
      '#description' => $this->t('Hide the breadcrumb when it only links to the home page and nothing more.'),
      '#default_value' => $config->get(BreadcrumbFieldConstants::HIDE_SINGLE_HOME_ITEM),
    ];

    $form = [];

    // Inserts the fieldset for grouping general settings fields.
    $form[BreadcrumbFieldConstants::MODULE_NAME] = $fieldset_general;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('breadcrumb_field.settings');

    $config
      ->set(BreadcrumbFieldConstants::INCLUDE_HOME_SEGMENT, $form_state->getValue(BreadcrumbFieldConstants::INCLUDE_HOME_SEGMENT))
      ->set(BreadcrumbFieldConstants::HOME_SEGMENT_TITLE, $form_state->getValue(BreadcrumbFieldConstants::HOME_SEGMENT_TITLE))
      ->set(BreadcrumbFieldConstants::HOME_SEGMENT_KEEP, $form_state->getValue(BreadcrumbFieldConstants::HOME_SEGMENT_KEEP))
      ->set(BreadcrumbFieldConstants::INCLUDE_TITLE_SEGMENT, $form_state->getValue(BreadcrumbFieldConstants::INCLUDE_TITLE_SEGMENT))
      ->set(BreadcrumbFieldConstants::TITLE_SEGMENT_AS_LINK, $form_state->getValue(BreadcrumbFieldConstants::TITLE_SEGMENT_AS_LINK))
      ->set(BreadcrumbFieldConstants::TITLE_FROM_PAGE_WHEN_AVAILABLE, $form_state->getValue(BreadcrumbFieldConstants::TITLE_FROM_PAGE_WHEN_AVAILABLE))
      ->set(BreadcrumbFieldConstants::HIDE_SINGLE_HOME_ITEM, $form_state->getValue(BreadcrumbFieldConstants::HIDE_SINGLE_HOME_ITEM))
      ->save();

    parent::submitForm($form, $form_state);

  }

}
