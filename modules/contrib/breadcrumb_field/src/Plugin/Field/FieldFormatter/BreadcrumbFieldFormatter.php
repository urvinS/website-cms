<?php

namespace Drupal\breadcrumb_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Plugin implementation of the 'breadcrumb_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "breadcrumb_field_formatter",
 *   label = @Translation("Breadcrumb field formatter"),
 *   field_types = {
 *     "breadcrumb_field_type"
 *   }
 * )
 */
class BreadcrumbFieldFormatter extends LinkFormatter {

  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Send items to Breadcrumb builder.
    \Drupal::service('breadcrumb_field.breadcrumb')->setFieldLinks($items);
    return [];
  }
}
