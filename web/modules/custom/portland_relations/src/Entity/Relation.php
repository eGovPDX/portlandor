<?php

namespace Drupal\portland_relations\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
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
 *       "revision-delete" = \Drupal\Core\Entity\Form\RevisionDeleteForm::class,
 *       "revision-revert" = \Drupal\Core\Entity\Form\RevisionRevertForm::class,
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\portland_relations\RelationHtmlRouteProvider",
 *       "revision" = \Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider::class,
 *     },
 *     "access" = "Drupal\portland_relations\RelationAccessControlHandler",
 *   },
 *   base_table = "relation",
 *   revision_table = "relation_revision",
 *   revision_data_table = "relation_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = FALSE,
 *   permission_granularity = "bundle",
 *   admin_permission = "administer relation entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message",
 *   },
 *   links = {
 *     "canonical" = "/relation/{relation}",
 *     "add-page" = "/relation/add",
 *     "add-form" = "/relation/add/{relation_type}",
 *     "edit-form" = "/relation/{relation}/edit",
 *     "delete-form" = "/relation/{relation}/delete",
 *     "revision" = "/relation/{relation}/revision/{relation_revision}/view",
 *     "revision-delete-form" = "/relation/{relation}/revision/{relation_revision}/delete",
 *     "revision-revert-form" = "/relation/{relation}/revision/{relation_revision}/revert",
 *     "version-history" = "/relation/{relation}/revisions",
 *     "collection" = "/admin/relation",
 *   },
 *   bundle_entity_type = "relation_type",
 *   field_ui_base_route = "entity.relation_type.edit_form"
 * )
 */
class Relation extends RevisionableContentEntityBase implements RelationInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * Since we have old relations that don't have a revision_created field in the database,
   * we return the entity creation time if it's null.
   */
  public function getRevisionCreationTime() {
    return parent::getRevisionCreationTime() ?? $this->getCreatedTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setRevisionable(TRUE)
      ->setDescription(t('The time that the entity was last edited.'));

    $fields += static::revisionLogBaseFieldDefinitions($entity_type);

    return $fields;
  }

}
