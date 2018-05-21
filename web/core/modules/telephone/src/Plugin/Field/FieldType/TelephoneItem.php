<?php

namespace Drupal\telephone\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'telephone' field type.
 *
 * @FieldType(
 *   id = "telephone",
 *   label = @Translation("Telephone number"),
 *   description = @Translation("This field stores a telephone number in the database."),
 *   category = @Translation("Number"),
 *   default_widget = "telephone_default",
 *   default_formatter = "basic_string"
 * )
 */
class TelephoneItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 256,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Telephone number'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();

    $min_length = 6;
    $max_length = 256;
    $constraints[] = $constraint_manager->create('ComplexData', [
      'value' => [
        'Length' => [
          'min' => $min_length,
          'minMessage' => t('%name: the telephone number may not be shorter than @min characters.', array('%name' => $this->getFieldDefinition()->getLabel(), '@min' => $min_length)),
          'max' => $max_length,
          'maxMessage' => t('%name: the telephone number may not be longer than @max characters.', ['%name' => $this->getFieldDefinition()->getLabel(), '@max' => $max_length]),
        ],
      ],
    ]);

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['value'] = rand(pow(10, 8), pow(10, 9) - 1);
    return $values;
  }

}
