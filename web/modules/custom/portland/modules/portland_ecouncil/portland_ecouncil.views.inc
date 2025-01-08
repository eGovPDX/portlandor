<?php

/**
 * Implements hook_views_data_alter().
 */
function portland_ecouncil_views_data_alter(array &$data) {
  $data['node']['node_council_document_impact_analysis'] = [
    'title' => t('Council document impact analysis status'),
    'group' => t('Content'),
    'filter' => [
      'title' => t('Council document impact analysis status'),
      'help' => t('For E-Council: Whether this council document needs or has an impact statement of a specific type.'),
      'id' => 'node_council_document_impact_analysis',
    ],
  ];
}
