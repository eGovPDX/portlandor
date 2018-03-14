<?php

namespace Drupal\jsonapi\Normalizer\Value;

/**
 * Helps normalize relationships in compliance with the JSON API spec.
 *
 * @internal
 */
class RelationshipNormalizerValue extends FieldNormalizerValue {

  /**
   * The link manager.
   *
   * @var \Drupal\jsonapi\LinkManager\LinkManager
   */
  protected $linkManager;

  /**
   * The JSON API resource type.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceType
   */
  protected $resourceType;

  /**
   * The field name for the link generation.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The entity ID for the host entity.
   *
   * @var string
   */
  protected $hostEntityId;

  /**
   * Instantiate a EntityReferenceNormalizerValue object.
   *
   * @param RelationshipItemNormalizerValue[] $values
   *   The normalized result.
   * @param int $cardinality
   *   The number of fields for the field list.
   * @param array $link_context
   *   All the objects and variables needed to generate the links for this
   *   relationship.
   */
  public function __construct(array $values, $cardinality, array $link_context) {
    $this->hostEntityId = $link_context['host_entity_id'];
    $this->fieldName = $link_context['field_name'];
    $this->linkManager = $link_context['link_manager'];
    $this->resourceType = $link_context['resource_type'];
    array_walk($values, function ($field_item_value) {
      if (!$field_item_value instanceof RelationshipItemNormalizerValue) {
        throw new \RuntimeException(sprintf('Unexpected normalizer item value for this %s.', get_called_class()));
      }
    });
    parent::__construct($values, $cardinality);
  }

  /**
   * {@inheritdoc}
   */
  public function rasterizeValue() {
    $links = $this->getLinks($this->resourceType->getPublicName($this->fieldName));
    // Empty 'to-one' relationships must be NULL.
    // Empty 'to-many' relationships must be an empty array.
    // @link http://jsonapi.org/format/#document-resource-object-linkage
    $data = parent::rasterizeValue() ?: [];
    return empty($data) && $this->cardinality == 1
      ? ['data' => NULL, 'links' => $links]
      : ['data' => $data, 'links' => $links];
  }

  /**
   * Gets the links for the relationship.
   *
   * @param string $field_name
   *   The public field name for the relationship.
   *
   * @return array
   *   An array of links to be rasterized.
   */
  protected function getLinks($field_name) {
    $route_parameters = [
      'related' => $field_name,
    ];
    $links['self'] = $this->linkManager->getEntityLink(
      $this->hostEntityId,
      $this->resourceType,
      $route_parameters,
      'relationship'
    );
    $resource_types = $this->resourceType->getRelatableResourceTypesByField($field_name);
    if (static::hasNonInternalResourceType($resource_types)) {
      $links['related'] = $this->linkManager->getEntityLink(
        $this->hostEntityId,
        $this->resourceType,
        $route_parameters,
        'related'
      );
    }
    return $links;
  }

  /**
   * Determines if a given list of resource types contains a non-internal type.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
   *   The JSON API resource types to evaluate.
   *
   * @return bool
   *   FALSE if every resource type is internal, TRUE otherwise.
   */
  protected static function hasNonInternalResourceType(array $resource_types) {
    foreach ($resource_types as $resource_type) {
      if (!$resource_type->isInternal()) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
