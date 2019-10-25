<?php

namespace Drupal\portland_relations\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Relation type entity.
 *
 * @ConfigEntityType(
 *   id = "relation_type",
 *   label = @Translation("Relation type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\portland_relations\RelationTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\portland_relations\Form\RelationTypeForm",
 *       "edit" = "Drupal\portland_relations\Form\RelationTypeForm",
 *       "delete" = "Drupal\portland_relations\Form\RelationTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\portland_relations\RelationTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "relation_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "relation",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/relation_type/{relation_type}",
 *     "add-form" = "/admin/structure/relation_type/add",
 *     "edit-form" = "/admin/structure/relation_type/{relation_type}/edit",
 *     "delete-form" = "/admin/structure/relation_type/{relation_type}/delete",
 *     "collection" = "/admin/structure/relation_type"
 *   }
 * )
 */
class RelationType extends ConfigEntityBundleBase implements RelationTypeInterface {

  /**
   * The Relation type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Relation type label.
   *
   * @var string
   */
  protected $label;

}
