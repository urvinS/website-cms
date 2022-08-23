<?php

namespace Drupal\decoupled_kit_router\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\decoupled_kit_router\DecoupledKitRouterInterface;

/**
 * Defines the decoupled_kit_router entity type.
 *
 * @ConfigEntityType(
 *   id = "decoupled_kit_router",
 *   label = @Translation("Decoupled Kit Router"),
 *   label_collection = @Translation("Decoupled Kit Routers"),
 *   label_singular = @Translation("Decoupled Kit Router"),
 *   label_plural = @Translation("Decoupled Kit Routers"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Decoupled Kit Router",
 *     plural = "@count Decoupled Kit Routers",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\decoupled_kit_router\DecoupledKitRouterListBuilder",
 *     "form" = {
 *       "add" = "Drupal\decoupled_kit_router\Form\DecoupledKitRouterForm",
 *       "edit" = "Drupal\decoupled_kit_router\Form\DecoupledKitRouterForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "decoupled_kit_router",
 *   admin_permission = "administer decoupled_kit_router",
 *   links = {
 *     "collection" = "/admin/structure/decoupled-kit-router",
 *     "add-form" = "/admin/structure/decoupled-kit-router/add",
 *     "edit-form" = "/admin/structure/decoupled-kit-router/{decoupled_kit_router}",
 *     "delete-form" = "/admin/structure/decoupled-kit-router/{decoupled_kit_router}/delete"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "entity_type",
 *     "entity_bundle",
 *     "router_path"
 *   }
 * )
 */
class DecoupledKitRouter extends ConfigEntityBase implements DecoupledKitRouterInterface {

  /**
   * The decoupled_kit_router ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The decoupled_kit_router label.
   *
   * @var string
   */
  protected $label;

  /**
   * The decoupled_kit_router entity_type.
   *
   * @var string
   */
   protected $entity_type;

  /**
   * The decoupled_kit_router entity_bundle.
   *
   * @var string
   */
  protected $entity_bundle;

  /**
   * The decoupled_kit_router router path.
   *
   * @var string
   */
  protected $router_path;

  /**
   * The decoupled_kit_router status.
   *
   * @var bool
   */
  protected $status;

}
