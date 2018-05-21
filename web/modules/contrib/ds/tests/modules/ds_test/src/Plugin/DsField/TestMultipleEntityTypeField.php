<?php

namespace Drupal\ds_test\Plugin\DsField;

use Drupal\ds\Plugin\DsField\DsFieldBase;

/**
 * Multiple entity type field plugin.
 *
 * @DsField(
 *   id = "test_multiple_entity_test_field",
 *   title = @Translation("Multiple entity test field plugin"),
 *   entity_type = {"node", "user"}
 * )
 */
class TestMultipleEntityTypeField extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return ['#markup' => 'Multiple entity test field plugin'];
  }

}
