<?php

namespace Drupal\jsonapi\Normalizer\Value;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;

/**
 * @internal
 */
class RelationshipItemNormalizerValue extends FieldItemNormalizerValue implements RefinableCacheableDependencyInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * Resource path.
   *
   * @param string
   */
  protected $resource;

  /**
   * Instantiates a EntityReferenceItemNormalizerValue object.
   *
   * @param array $values
   *   The values.
   * @param string $resource
   *   The resource type of the target entity.
   */
  public function __construct(array $values, $resource) {
    parent::__construct($values);
    $this->resource = $resource;
  }

  /**
   * {@inheritdoc}
   */
  public function rasterizeValue() {
    if (!$value = parent::rasterizeValue()) {
      return $value;
    }
    $rasterized_value = [
      'type' => $this->resource->getTypeName(),
      'id' => empty($value['target_uuid']) ? $value : $value['target_uuid'],
    ];

    if (!empty($value['meta'])) {
      $rasterized_value['meta'] = $value['meta'];
    }

    return $rasterized_value;
  }

  /**
   * Sets the resource.
   *
   * @param string $resource
   *   The resource to set.
   */
  public function setResource($resource) {
    $this->resource = $resource;
  }

}
