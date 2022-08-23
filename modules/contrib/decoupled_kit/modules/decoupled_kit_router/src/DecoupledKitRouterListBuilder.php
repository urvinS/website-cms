<?php

namespace Drupal\decoupled_kit_router;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of decoupled_kit_routers.
 */
class DecoupledKitRouterListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['entity_type'] = $this->t('Entity type');
    $header['entity_bundle'] = $this->t('Entity bundle');
    $header['router_path'] = $this->t('Path');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\decoupled_kit_router\DecoupledKitRouterInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['entity_type'] = $entity->get('entity_type');
    $row['entity_bundle'] = $entity->get('entity_bundle');
    $row['router_path'] = $entity->get('router_path');
    $row['status'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');
    return $row + parent::buildRow($entity);
  }

}
