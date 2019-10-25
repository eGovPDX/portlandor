<?php

namespace Drupal\portland_relations\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Relation entity.
 *
 * @ingroup portland_relations
 *
 * @ContentEntityType(
 *   id = "relation",
 *   label = @Translation("Relation"),
 *   bundle_label = @Translation("Relation type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\portland_relations\RelationListBuilder",
 *     "views_data" = "Drupal\portland_relations\Entity\RelationViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\portland_relations\Form\RelationForm",
 *       "add" = "Drupal\portland_relations\Form\RelationForm",
 *       "edit" = "Drupal\portland_relations\Form\RelationForm",
 *       "delete" = "Drupal\portland_relations\Form\RelationDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\portland_relations\RelationHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\portland_relations\RelationAccessControlHandler",
 *   },
 *   base_table = "relation",
 *   translatable = FALSE,
 *   permission_granularity = "bundle",
 *   admin_permission = "administer relation entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/relation/{relation}",
 *     "add-page" = "/admin/structure/relation/add",
 *     "add-form" = "/admin/structure/relation/add/{relation_type}",
 *     "edit-form" = "/admin/structure/relation/{relation}/edit",
 *     "delete-form" = "/admin/structure/relation/{relation}/delete",
 *     "collection" = "/admin/structure/relation",
 *   },
 *   bundle_entity_type = "relation_type",
 *   field_ui_base_route = "entity.relation_type.edit_form"
 * )
 */
class Relation extends ContentEntityBase implements RelationInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    return $fields;
  }

}
