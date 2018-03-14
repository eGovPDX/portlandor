<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\jsonapi\Normalizer\Value\ConfigFieldItemNormalizerValue;
use Drupal\jsonapi\Normalizer\Value\FieldNormalizerValue;
use Drupal\jsonapi\ResourceType\ResourceType;

/**
 * Converts the Drupal config entity object to a JSON API array structure.
 *
 * @internal
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
    $fields = static::getDataWithoutInternals($entity->toArray());
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
    $output = new FieldNormalizerValue(
      [new ConfigFieldItemNormalizerValue($field)],
      1
    );
    $output->setPropertyType('attributes');
    return $output;
  }

  /**
   * Gets the given data without the internal implementation details.
   *
   * @param array $data
   *   The data that is either currently or about to be stored in configuration.
   *
   * @return array
   *   The same data, but without internals. Currently, that is only the '_core'
   *   key, which is reserved by Drupal core to handle complex edge cases
   *   correctly. Data in the '_core' key is irrelevant to clients reading
   *   configuration, and is not allowed to be set by clients writing
   *   configuration: it is for Drupal core only, and managed by Drupal core.
   *
   * @see https://www.drupal.org/node/2653358
   * @see \Drupal\serialization\Normalizer\ConfigEntityNormalizer::getDataWithoutInternals
   */
  protected static function getDataWithoutInternals(array $data) {
    return array_diff_key($data, ['_core' => TRUE]);
  }

}
