<?php

/**
 * Implements hook_views_data().
 */
function portland_smartsheet_views_data() {
  $data = [];
  // Base data.
  $data['smartsheet']['table']['group'] = t('Smartsheet');
  $data['smartsheet']['table']['base'] = [
    'title' => t('Smartsheet sheet'),
    'help' => t('Data from a Smartsheet sheet'),
    'query_id' => 'smartsheet'
  ];

  // Fields.
  $data['smartsheet']['cell'] = [
    'title' => t('Cell Data'),
    'help' => t('Data from a cell in current row of the sheet'),
    'field' => [
      'id' => 'smartsheet_cell'
    ]
  ];

  return $data;
}
