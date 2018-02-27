<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\jsonapi\Normalizer\Value\FieldItemNormalizerValue;
use Drupal\jsonapi\Normalizer\Value\FieldNormalizerValue;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * The normalizer used for scalar inputs.
 */
class ScalarNormalizer extends NormalizerBase {

  /**
   * {@inheritdoc}
   */
  protected $formats = ['api_json'];

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return (!$data || is_scalar($data)) && in_array($format, $this->formats);
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $value = new FieldItemNormalizerValue(['value' => $object]);
    return new FieldNormalizerValue([$value], 1);
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    throw new UnexpectedValueException('Denormalization not implemented for JSON API');
  }

}
