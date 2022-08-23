<?php

namespace Drupal\menu_normalizer\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * MenuLinkTreeElement Normalizer.
 */
class MenuLinkTreeNormalizer extends NormalizerBase {

  /**
   * Supported Interface or Class.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Menu\MenuLinkTreeElement';

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    return [
      'link' => $this->serializer->normalize($object->link, $format, $context),
      'has_children' => $object->hasChildren,
      'depth' => $object->depth,
      'in_active_trail' => $object->inActiveTrail,
      'subtree' => $this->serializer->normalize($object->subtree, $format, $context),
      'count' => $object->count(),
    ];
  }

}
