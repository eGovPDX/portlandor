<?php

namespace Drupal\portland_smartsheet\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\StringArgument;

/**
 * Provide an argument on Smartsheet views to filter a specific row ID
 *
 * @ViewsArgument("smartsheet_row_id")
 */
class SmartsheetRowIdArgument extends StringArgument {
  /**
   * {@inheritdoc}
   */
  public function query($group_by = false) {
    $row_id = $this->getValue();
    $this->query->addWhereRowId($row_id);
  }
}
