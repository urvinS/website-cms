<?php

namespace Drupal\breadcrumb_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\link\Plugin\Field\FieldType\LinkItem;

/**
 * Plugin implementation of the 'breadcrumb_field_type' field type.
 *
 * @FieldType(
 *   id = "breadcrumb_field_type",
 *   label = @Translation("Breadcrumb field"),
 *   description = @Translation("Breadcrumb field type"),
 *   default_widget = "link_default",
 *   default_formatter = "link"
 * )
 */
class BreadcrumbFieldType extends LinkItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    return parent::propertyDefinitions($field_definition);
  }
}
