<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\jsonapi\Normalizer\Value\FieldItemNormalizerValue;
use Drupal\jsonapi\Normalizer\Value\FieldNormalizerValue;
use Drupal\jsonapi\ResourceType\ResourceType;

/**
 * Converts the Drupal config entity object to a JSON API array structure.
 */
class ConfigEntityNormalizer extends EntityNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = ConfigEntityInterface::class;

  /**
   * {@inheritdoc}
   */
  protected function getFields($entity, $bundle, ResourceType $resource_type) {
    $enabled_public_fields = [];
    $fields = $entity->toArray();
    // Filter the array based on the field names.
    $enabled_field_names = array_filter(
      array_keys($fields),
      [$resource_type, 'isFieldEnabled']
    );
    // Return a sub-array of $output containing the keys in $enabled_fields.
    $input = array_intersect_key($fields, array_flip($enabled_field_names));
    /* @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    foreach ($input as $field_name => $field_value) {
      $public_field_name = $resource_type->getPublicName($field_name);
      $enabled_public_fields[$public_field_name] = $field_value;
    }
    return $enabled_public_fields;
  }

  /**
   * {@inheritdoc}
   */
  protected function serializeField($field, array $context, $format) {
    $output = $this->serializer->normalize($field, $format, $context);
    if (is_array($output)) {
      $output = new FieldNormalizerValue(
        [new FieldItemNormalizerValue($output)],
        1
      );
      $output->setPropertyType('attributes');
      return $output;
    }
    $field instanceof Relationship ?
      $output->setPropertyType('relationships') :
      $output->setPropertyType('attributes');
    return $output;
  }

}
