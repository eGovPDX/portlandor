<?php

namespace Drupal\example_field\Plugin\DsField;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Generated field.
 *
 * @DsField(
 *   id = "example_field_ExampleField",
 *   title = @Translation("ExampleField"),
 *   entity_type = "node"
 * )
 */
class ExampleField extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function formatters() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed() {
    return TRUE;
  }

}
