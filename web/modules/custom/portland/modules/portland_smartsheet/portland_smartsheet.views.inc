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
  $data['smartsheet']['attachments'] = [
    'title' => t('Attachments'),
    'help' => t('Attachments from the current row'),
    'field' => [
      'id' => 'smartsheet_attachments',
    ],
  ];
  $data['smartsheet']['cell'] = [
    'title' => t('Cell Data'),
    'help' => t('Data from a cell in current row of the sheet'),
    'field' => [
      'id' => 'smartsheet_cell',
    ],
    'sort' => [
      'id' => 'smartsheet_cell',
    ],
  ];
  $data['smartsheet']['row_id'] = [
    'title' => t('Row ID'),
    'help' => t('ID of the current row'),
    'argument' => [
      'id' => 'smartsheet_row_id',
    ],
    'field' => [
      'id' => 'smartsheet_row_id',
    ],
  ];

  return $data;
}
