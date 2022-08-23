<?php

namespace Drupal\metatag\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\metatag\TypedData\ComputedItemListTrait;

/**
 * Represents the computed metatags for an entity.
 */
class MetatagNormalizedFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * Whether the metatags have been generated.
   *
   * This allows the cached value to be recomputed after the entity is saved.
   *
   * @var bool
   */
  protected $metatagsGenerated = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function valueNeedsRecomputing() {
    return !$this->getEntity()->isNew() && !$this->metatagsGenerated;
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    if ($entity->isNew()) {
      return;
    }
    /** @var \Drupal\metatag\MetatagManagerInterface $metatag_manager */
    $metatag_manager = \Drupal::service('metatag.manager');
    $metatags_for_entity = $metatag_manager->tagsFromEntityWithDefaults($entity);
    $tags = $metatag_manager->generateRawElements($metatags_for_entity, $entity);
    $this->list = [];
    $offset = 0;
    foreach ($tags as $tag) {
      $item = [
        'tag' => $tag['#tag'],
        'attributes' => $tag['#attributes'],
      ];
      $this->list[] = $this->createItem($offset, $item);
      $offset++;
    }

    $this->metatagsGenerated = TRUE;
  }

}