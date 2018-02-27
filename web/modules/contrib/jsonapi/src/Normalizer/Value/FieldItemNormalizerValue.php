<?php

namespace Drupal\jsonapi\Normalizer\Value;

/**
 * @internal
 */
class FieldItemNormalizerValue implements ValueExtractorInterface {

  /**
   * Raw values.
   *
   * @param array
   */
  protected $raw;

  /**
   * Included entity objects.
   *
   * @param \Drupal\jsonapi\Normalizer\Value\EntityNormalizerValue
   */
  protected $include;

  /**
   * Instantiate a FieldItemNormalizerValue object.
   *
   * @param array $values
   *   The normalized result.
   */
  public function __construct(array $values) {
    $this->raw = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function rasterizeValue() {
    // If there is only one property, then output it directly.
    $value = count($this->raw) == 1 ? reset($this->raw) : $this->raw;

    return $this->rasterizeValueRecursive($value);
  }

  /**
   * {@inheritdoc}
   */
  public function rasterizeIncludes() {
    return $this->include->rasterizeValue();
  }

  /**
   * Add an include.
   *
   * @param ValueExtractorInterface $include
   *   The included entity.
   */
  public function setInclude(ValueExtractorInterface $include) {
    $this->include = $include;
  }

  /**
   * Gets the include.
   *
   * @return \Drupal\jsonapi\Normalizer\Value\EntityNormalizerValue
   *   The include.
   */
  public function getInclude() {
    return $this->include;
  }

  /**
   * Rasterizes a value recursively.
   *
   * This is mainly for configuration entities where a field can be a tree of
   * values to rasterize.
   *
   * @param mixed $value
   *   Either a scalar, an array or a rasterizable object.
   *
   * @return mixed
   *   The rasterized value.
   */
  protected function rasterizeValueRecursive($value) {
    if (!$value || is_scalar($value)) {
      return $value;
    }
    if (is_array($value)) {
      $output = [];
      foreach ($value as $key => $item) {
        $output[$key] = $this->rasterizeValueRecursive($item);
      }

      return $output;
    }
    if ($value instanceof ValueExtractorInterface) {
      return $value->rasterizeValue();
    }
    // If the object can be turned into a string it's better than nothing.
    if (method_exists($value, '__toString')) {
      return $value->__toString();
    }

    // We give up, since we do not know how to rasterize this.
    return NULL;
  }

}
