<?php

namespace Drupal\jsonapi\ResourceType;

/**
 * Value object containing all metadata for a JSON API resource type.
 *
 * Used to generate routes (collection, individual, etcetera), generate
 * relationship links, and so on.
 *
 * @see \Drupal\jsonapi\ResourceType\ResourceTypeRepository
 *
 * @api
 */
class ResourceType {

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The bundle ID.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The type name.
   *
   * @var string
   */
  protected $typeName;

  /**
   * The class to which a payload converts to.
   *
   * @var string
   */
  protected $deserializationTargetClass;

  /**
   * Gets the entity type ID.
   *
   * @return string
   *   The entity type ID.
   *
   * @see \Drupal\Core\Entity\EntityInterface::getEntityTypeId
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * Gets the type name.
   *
   * @return string
   *   The type name.
   */
  public function getTypeName() {
    return $this->typeName;
  }

  /**
   * Gets the bundle.
   *
   * @return string
   *   The bundle of the entity. Defaults to the entity type ID if the entity
   *   type does not make use of different bundles.
   *
   * @see \Drupal\Core\Entity\EntityInterface::bundle
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * Gets the deserialization target class.
   *
   * @return string
   *   The deserialization target class.
   */
  public function getDeserializationTargetClass() {
    return $this->deserializationTargetClass;
  }

  /**
   * Translates the entity field name to the public field name.
   *
   * This is only here so we can allow polymorphic implementations to take a
   * greater control on the field names.
   *
   * @return string
   *   The public field name.
   */
  public function getPublicName($field_name) {
    // By default the entity field name is the public field name.
    return $field_name;
  }

  /**
   * Translates the public field name to the entity field name.
   *
   * This is only here so we can allow polymorphic implementations to take a
   * greater control on the field names.
   *
   * @return string
   *   The internal field name as defined in the entity.
   */
  public function getInternalName($field_name) {
    // By default the entity field name is the public field name.
    return $field_name;
  }

  /**
   * Checks if a field is enabled or not.
   *
   * This is only here so we can allow polymorphic implementations to take a
   * greater control on the data model.
   *
   * @param string $field_name
   *   The internal field name.
   *
   * @return bool
   *   TRUE if the field is enabled and should be considered as part of the data
   *   model. FALSE otherwise.
   */
  public function isFieldEnabled($field_name) {
    // By default all fields are enabled.
    return TRUE;
  }

  /**
   * Determine whether to include a collection count.
   *
   * @return bool
   *   Whether to include a collection count.
   */
  public function includeCount() {
    // By default, do not return counts in collection queries.
    return FALSE;
  }

  /**
   * Instantiates a ResourceType object.
   *
   * @param string $entity_type_id
   *   An entity type ID.
   * @param string $bundle
   *   A bundle.
   * @param string $deserialization_target_class
   *   The deserialization target class.
   */
  public function __construct($entity_type_id, $bundle, $deserialization_target_class) {
    $this->entityTypeId = $entity_type_id;
    $this->bundle = $bundle;
    $this->deserializationTargetClass = $deserialization_target_class;

    $this->typeName = sprintf('%s--%s', $this->entityTypeId, $this->bundle);
  }

}
