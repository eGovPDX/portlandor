<?php

/**
 * Implements hook_views_data().
 */
function portland_zendesk_views_data() {
  $data = [];
  // Base data.
  $data['zendesk_ticket']['table']['group'] = t('Zendesk ticket');
  $data['zendesk_ticket']['table']['base'] = [
    'title' => t('Zendesk ticket'),
    'help' => t('Zendesk ticket data provided by the Zendesk Tickets API.'),
    'query_id' => 'zendesk',
  ];

  // Fields.
  $data['zendesk_ticket']['ticket_id'] = [
    'title' => t('Ticket ID'),
    'help' => t('The ID of the associated Zendesk ticket.'),
    'field' => [
      'id' => 'numeric',
    ],
  ];
  $data['zendesk_ticket']['ticket_status'] = [
    'title' => t('Ticket status'),
    'help' => t('The status of the ticket.'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['zendesk_ticket']['ticket_subject'] = [
    'title' => t('Ticket subject'),
    'help' => t('The ticket subject.'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['zendesk_ticket']['ticket_description'] = [
    'title' => t('Ticket description'),
    'help' => t('The ticket description (first comment).'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['zendesk_ticket']['ticket_priority'] = [
    'title' => t('Ticket priority'),
    'help' => t('The ticket priority.'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['zendesk_ticket']['ticket_created_date'] = [
    'title' => t('Ticket creation date'),
    'help' => t('The date the ticket was created.'),
    'field' => [
      'id' => 'date',
    ],
  ];
  $data['zendesk_ticket']['ticket_updated_date'] = [
    'title' => t('Ticket last updated date'),
    'help' => t('The date the ticket was last updated.'),
    'field' => [
      'id' => 'date',
    ],
  ];
  $data['zendesk_ticket']['custom_asset_id'] = [
    'title' => t('Ticket asset id'),
    'help' => t('The asset ID associated with the ticket.'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['zendesk_ticket']['custom_reported_issue'] = [
    'title' => t('Ticket reported issue'),
    'help' => t('The reported issue associated with the ticket.'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['zendesk_ticket']['custom_location_lat'] = [
    'title' => t('Ticket location latitude'),
    'help' => t('The latitude coordinate associated with the ticket.'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['zendesk_ticket']['custom_location_lon'] = [
    'title' => t('Ticket location longitude'),
    'help' => t('The longitude coordinate associated with the ticket.'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['zendesk_ticket']['custom_address'] = [
    'title' => t('Ticket location address'),
    'help' => t('The street address associated with the ticket.'),
    'field' => [
      'id' => 'standard',
    ],
  ];
  $data['zendesk_ticket']['custom_public_description'] = [
    'title' => t('Public description'),
    'help' => t('A public-friendly description of the incident.'),
    'field' => [
      'id' => 'standard',
    ],
  ];


  return $data;
}

