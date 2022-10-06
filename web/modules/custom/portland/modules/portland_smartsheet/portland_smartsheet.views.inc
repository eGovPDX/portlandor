<?php

/**
 * Implements hook_views_data().
 */
function portland_smartsheet_views_data() {
  $data = [];
  // Base data.
  $data['smartsheet']['table']['group'] = t('Smartsheet');
  $data['smartsheet']['table']['base'] = [
    'title' => t('Smartsheet'),
    'help' => t('Populate a view using the Smartsheet API'),
    'query_id' => 'smartsheet',
  ];

  // Fields.
  $data['smartsheet']['cell'] = [
    'title' => t('Cell Data'),
    'help' => t('Data from a cell in current row of the sheet'),
    'field' => [
      'id' => 'smartsheet_cell'
    ],
    'sort' => [
      'id' => 'smartsheet_cell'
    ],
  ];

  return $data;
}
