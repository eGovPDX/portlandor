<?php

namespace Drupal\portland_smartsheet\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Provide a field on Smartsheet views to get the row ID
 *
 * @ViewsField("smartsheet_row_id")
 */
class SmartsheetRowIdField extends FieldPluginBase {
  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $row, $field = null) {
    return $row->cells['_data']->id;
  }
}
