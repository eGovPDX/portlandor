<?php

/**
 * Implements hook_views_data_alter().
 */
function portland_views_data_alter(array &$data) {
  $data['node']['redirects'] = array(
    'title' => t('Redirects'),
    'group' => t('Content'),
    'field' => array(
      'title' => t('Redirects'),
      'help' => t('Redirect from this node.'),
      'id' => 'node_redirects',
    ),
  );

  $data['node']['aliases'] = array(
    'title' => t('Aliases'),
    'group' => t('Content'),
    'field' => array(
      'title' => t('Aliases'),
      'help' => t('Aliases for this node.'),
      'id' => 'node_aliases',
    ),
  );
}
