<?php

/**
 * Implements hook_views_data().
 */
function fitbit_views_example_views_data() {
  $data = [];
  // Base data.
  $data['fitbit_profile']['table']['group'] = t('Fitbit profile');
  $data['fitbit_profile']['table']['base'] = [
    'title' => t('Fitbit profile'),
    'help' => t('Fitbit profile data provided by the Fitbit API.'),
    'query_id' => 'fitbit',
  ];

  // Fields.
  $data['fitbit_profile']['ticket_id'] = [
    'title' => t('Ticket ID'),
    'help' => t('The ID of the associated Zendesk ticket.'),
    'field' => [
      'id' => 'numeric',
    ],
  ];
  $data['fitbit_profile']['ticket_status'] = [
    'title' => t('Ticket status'),
    'help' => t('The status of the ticket.'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['fitbit_profile']['ticket_subject'] = [
    'title' => t('Ticket subject'),
    'help' => t('The ticket subject.'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['fitbit_profile']['ticket_description'] = [
    'title' => t('Ticket description'),
    'help' => t('The ticket description (first comment).'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['fitbit_profile']['ticket_priority'] = [
    'title' => t('Ticket priority'),
    'help' => t('The ticket priority.'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['fitbit_profile']['ticket_created_date'] = [
    'title' => t('Ticket creation date'),
    'help' => t('The date the ticket was created.'),
    'field' => [
      'id' => 'date',
    ],
  ];
  $data['fitbit_profile']['ticket_updated_date'] = [
    'title' => t('Ticket last updated date'),
    'help' => t('The date the ticket was last updated.'),
    'field' => [
      'id' => 'date',
    ],
  ];
  $data['fitbit_profile']['custom_location_lat'] = [
    'title' => t('Ticket location latitude'),
    'help' => t('The latitude coordinate associated with the ticket.'),
    'field' => [
      'id' => 'numeric',
    ],
  ];
  $data['fitbit_profile']['custom_location_lon'] = [
    'title' => t('Ticket location longitude'),
    'help' => t('The longitude coordinate associated with the ticket.'),
    'field' => [
      'id' => 'numeric',
    ],
  ];
  $data['fitbit_profile']['custom_address'] = [
    'title' => t('Ticket location address'),
    'help' => t('The street address associated with the ticket.'),
    'field' => [
      'id' => 'standard',
    ],
  ];

  return $data;
}

