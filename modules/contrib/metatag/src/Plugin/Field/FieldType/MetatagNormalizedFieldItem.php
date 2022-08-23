<?php

namespace Drupal\metatag\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'metatag_normalized' field type.
 *
 * @FieldType(
 *   id = "metatag_normalized",
 *   label = @Translation("Meta tags normalized"),
 *   description = @Translation("Computed normalized meta tags"),
 *   no_ui = TRUE,
 *   list_class = "\Drupal\metatag\Plugin\Field\FieldType\MetatagNormalizedFieldItemList",
 * )
 */
class MetatagNormalizedFieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['tag'] = DataDefinition::create('string')
      ->setLabel(t('Tag'))
      ->setRequired(TRUE);
    $properties['attributes'] = DataDefinition::create('any')
      ->setLabel(t('Name'))
      ->setRequired(TRUE);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('attributes')->getValue();
    return $value === NULL || $value === serialize([]);
  }

}