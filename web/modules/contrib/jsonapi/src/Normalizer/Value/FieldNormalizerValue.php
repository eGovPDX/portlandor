<?php

namespace Drupal\jsonapi\Normalizer\Value;

use Drupal\Core\Cache\RefinableCacheableDependencyTrait;

/**
 * Helps normalize fields in compliance with the JSON API spec.
 *
 * @internal
 */
class FieldNormalizerValue implements FieldNormalizerValueInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * The values.
   *
   * @var array
   */
  protected $values;

  /**
   * The includes.
   *
   * @var array
   */
  protected $includes;

  /**
   * The field cardinality.
   *
   * @var int
   */
  protected $cardinality;

  /**
   * The property type. Either: 'attributes' or `relationships'.
   *
   * @var string
   */
  protected $propertyType;

  /**
   * Instantiate a FieldNormalizerValue object.
   *
   * @param \Drupal\jsonapi\Normalizer\Value\FieldItemNormalizerValue[] $values
   *   The normalized result.
   * @param int $cardinality
   *   The cardinality of the field list.
   */
  public function __construct(array $values, $cardinality) {
    $this->values = $values;
    $this->includes = array_map(function ($value) {
      if (!$value instanceof FieldItemNormalizerValue) {
        return new NullFieldNormalizerValue();
      }
      return $value->getInclude();
    }, $values);
    $this->includes = array_filter($this->includes);
    $this->cardinality = $cardinality;
  }

  /**
   * {@inheritdoc}
   */
  public function rasterizeValue() {
    if (empty($this->values)) {
      return NULL;
    }

    if ($this->cardinality == 1) {
      assert(count($this->values) === 1);
      return $this->values[0] instanceof FieldItemNormalizerValue
        ? $this->values[0]->rasterizeValue() : NULL;
    }

    return array_map(function ($value) {
      return $value instanceof FieldItemNormalizerValue ? $value->rasterizeValue() : NULL;
    }, $this->values);
  }

  /**
   * {@inheritdoc}
   */
  public function rasterizeIncludes() {
    return array_map(function ($include) {
      return $include->rasterizeValue();
    }, $this->includes);
  }

  /**
   * {@inheritdoc}
   */
  public function getIncludes() {
    return $this->includes;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyType() {
    return $this->propertyType;
  }

  /**
   * {@inheritdoc}
   */
  public function setPropertyType($property_type) {
    $this->propertyType = $property_type;
  }

  /**
   * {@inheritdoc}
   */
  public function setIncludes(array $includes) {
    $this->includes = $includes;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllIncludes() {
    $nested_includes = array_map(function ($include) {
      return $include->getIncludes();
    }, $this->getIncludes());
    $includes = array_reduce(array_filter($nested_includes), function ($carry, $item) {
      return array_merge($carry, $item);
    }, $this->getIncludes());
    // Make sure we don't output duplicate includes.
    return array_values(array_reduce($includes, function ($unique_includes, $include) {
      $rasterized_include = $include->rasterizeValue();
      $unique_includes[$rasterized_include['data']['type'] . ':' . $rasterized_include['data']['id']] = $include;
      return $unique_includes;
    }, []));
  }

}
