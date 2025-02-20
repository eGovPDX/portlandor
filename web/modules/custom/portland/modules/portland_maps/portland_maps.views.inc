<?php

/**
 * Implements hook_views_data().
 */
function portland_maps_views_data()
{
  $data['portland_maps']['table']['group'] = t('Portland Maps Data');
  $data['portland_maps']['table']['provider'] = 'portland_maps';
  $data['portland_maps']['table']['base'] = [
    'title' => t('Portland Maps Data'),
    'help' => t('Data from the PortlandMaps API.'),
    'query_id' => 'portland_maps',
    'type' => '',
  ];

  $data['portland_maps']['owner_name'] = [
    'title' => t('Owner Name'),
    'help' => t('The name of the property owner'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard',
  ];

  $data['portland_maps']['owner_address'] = [
    'title' => t('Owner Address'),
    'help' => t('The address of the property owner'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard',
  ];

  $data['portland_maps']['address'] = [
    'title' => t('Address'),
    'help' => t('The address of the property'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard',
  ];

  $data['portland_maps']['address2'] = [
    'title' => t('Address 2'),
    'help' => t('Additional address information'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard',
  ];

  $data['portland_maps']['block'] = [
    'title' => t('Block'),
    'help' => t('The block identifier for the property'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard',
  ];

  $data['portland_maps']['city'] = [
    'title' => t('City'),
    'help' => t('The city where the property is located'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard',
  ];

  $data['portland_maps']['property_id'] = [
    'title' => t('Property ID'),
    'help' => t('The unique identifier for the property'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard',
  ];

  $data['portland_maps']['tax_roll'] = [
    'title' => t('Tax Roll'),
    'help' => t('The tax roll information for the property'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard',
  ];

  $data['portland_maps']['use'] = [
    'title' => t('Use'),
    'help' => t('The designated use of the property'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard',
  ];

  $data['portland_maps']['lot'] = [
    'title' => t('Lot'),
    'help' => t('The lot number for the property'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard',
  ];

  $data['portland_maps']['county'] = [
    'title' => t('County'),
    'help' => t('The county where the property is located'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard',
  ];

  $data['portland_maps']['state_id'] = [
    'title' => t('State ID'),
    'help' => t('The state identifier for the property'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard',
  ];

  $data['portland_maps']['new_state_id'] = [
    'title' => t('New State ID'),
    'help' => t('The updated state identifier for the property'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard',
  ];

  $data['portland_maps']['parent_state_id'] = [
    'title' => t('Parent State ID'),
    'help' => t('The parent state identifier for the property'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard',
  ];

  $data['portland_maps']['alt_account_number'] = [
    'title' => t('Alt Account Number'),
    'help' => t('An alternative account number for the property'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard',
  ];

  $data['portland_maps']['map_number'] = [
    'title' => t('Map Number'),
    'help' => t('The map number associated with the property'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard',
  ];

  $data['portland_maps']['tax_code'] = [
    'title' => t('Tax Code'),
    'help' => t('The tax code assigned to the property'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard',
  ];

  $data['portland_maps']['x'] = [
    'title' => t('Location X Coordinate'),
    'help' => t('The location X coordinate of the property'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard',
  ];

  $data['portland_maps']['y'] = [
    'title' => t('Location Y Coordinate'),
    'help' => t('The location Y coordinate of the property'),
    'field' => [
      'id' => 'standard',
    ],
    'type' => 'standard',
  ];

  $data['portland_maps']['detail_id'] = [
    'title' => t('Detail ID'),
    'help' => t('Passes the detail ID parameter to the API.'),
    'argument' => [
      'id' => 'string',
      'title' => t('Detail ID'),
      'help' => t('Passes the detail ID parameter directly to the API call.'),
      'filter' => FALSE,  // Prevents Views from applying SQL-based filtering
    ],
    'type' => 'string',
  ];

  return $data;
}
