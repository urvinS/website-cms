<?php

namespace Drupal\breadcrumb_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Plugin implementation of the 'breadcrumb_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "breadcrumb_field_widget",
 *   label = @Translation("Breadcrumb field widget"),
 *   field_types = {
 *     "breadcrumb_field_type"
 *   }
 * )
 */
class BreadcrumbFieldWidget extends LinkWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    return $element;
  }
}
