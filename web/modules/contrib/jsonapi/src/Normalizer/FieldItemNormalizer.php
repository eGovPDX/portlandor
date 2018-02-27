<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\jsonapi\Normalizer\Value\FieldItemNormalizerValue;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Converts the Drupal field item object to a JSON API array structure.
 */
class FieldItemNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = FieldItemInterface::class;

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $formats = ['api_json'];

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    /** @var \Drupal\Core\TypedData\TypedDataInterface $property */
    $values = [];
    // We normalize each individual property, so each can do their own casting,
    // if needed.
    foreach ($field_item as $property_name => $property) {
      $values[$property_name] = $this->serializer->normalize($property, $format, $context);
    }

    if (isset($context['langcode'])) {
      $values['lang'] = $context['langcode'];
    }
    return new FieldItemNormalizerValue($values);
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    throw new UnexpectedValueException('Denormalization not implemented for JSON API');
  }

}
