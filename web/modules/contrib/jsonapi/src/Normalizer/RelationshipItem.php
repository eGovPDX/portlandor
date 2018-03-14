<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;

/**
 * Value object representing a JSON API relationship item.
 *
 * @internal
 */
class RelationshipItem {

  /**
   * The target key name.
   *
   * @var string
   */
  protected $targetKey = 'target_id';

  /**
   * The target entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $targetEntity;

  /**
   * The target JSON API resource type.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceType
   */
  protected $targetResourceType;

  /**
   * The parent relationship.
   *
   * @var \Drupal\jsonapi\Normalizer\Relationship
   */
  protected $parent;

  /**
   * The list of metadata associated with this relationship item value.
   *
   * @var array
   */
  protected $metadata;

  /**
   * Relationship item constructor.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The JSON API resource type repository.
   * @param \Drupal\Core\Entity\EntityInterface $target_entity
   *   The entity this relationship points to.
   * @param \Drupal\jsonapi\Normalizer\Relationship $parent
   *   The parent of this item.
   * @param string $target_key
   *   The key name of the target relationship.
   * @param array $metadata
   *   The list of metadata associated with this relationship item value.
   */
  public function __construct(ResourceTypeRepositoryInterface $resource_type_repository, EntityInterface $target_entity, Relationship $parent, $target_key = 'target_id', array $metadata = []) {
    $this->targetResourceType = $resource_type_repository->get(
      $target_entity->getEntityTypeId(),
      $target_entity->bundle()
    );
    $this->targetKey = $target_key;
    $this->targetEntity = $target_entity;
    $this->parent = $parent;
    $this->metadata = $metadata;
  }

  /**
   * Gets the target entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The target entity of this relationship item.
   */
  public function getTargetEntity() {
    return $this->targetEntity;
  }

  /**
   * Gets the targetResourceConfig.
   *
   * @return mixed
   *   The target of this relationship item.
   */
  public function getTargetResourceType() {
    return $this->targetResourceType;
  }

  /**
   * Gets the relationship value.
   *
   * Defaults to the entity ID.
   *
   * @return string
   *   The value of this relationship item.
   */
  public function getValue() {
    return [
      'target_uuid' => $this->getTargetEntity()->uuid(),
      'meta' => $this->metadata,
    ];
  }

  /**
   * Gets the relationship object that contains this relationship item.
   *
   * @return \Drupal\jsonapi\Normalizer\Relationship
   *   The parent relationship of this item.
   */
  public function getParent() {
    return $this->parent;
  }

}
