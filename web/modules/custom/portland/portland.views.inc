<?php

/**
 * Implements hook_views_data_alter().
 */
function portland_views_data_alter(array &$data) {
  $data['node']['has_translations'] = [
    'title' => t('Has translations'),
    'group' => t('Content'),
    'filter' => [
      'title' => t('Has translations'),
      'help' => t('Whether this node has translations in non-default languages.'),
      'id' => 'node_has_translations',
    ],
  ];

  $data['node']['translated_into'] = [
    'title' => t('Translated into'),
    'group' => t('Content'),
    'field' => [
      'title' => t('Translated into'),
      'help' => t('A list of the non-default languages that this node has been translated into (if any).'),
      'id' => 'node_translated_into',
    ],
  ];

  $data['node']['redirects'] = [
    'title' => t('Redirects'),
    'group' => t('Content'),
    'field' => [
      'title' => t('Redirects'),
      'help' => t('Redirect from this node.'),
      'id' => 'node_redirects',
    ],
  ];

  $data['node']['aliases'] = [
    'title' => t('Aliases'),
    'group' => t('Content'),
    'field' => [
      'title' => t('Aliases'),
      'help' => t('Aliases for this node.'),
      'id' => 'node_aliases',
    ],
  ];
}
