<?php

/**
 * Implements hook_views_data().
 */
function portland_zendesk_views_data() {
  $data = [];
  // Base data.
  $data['zendesk_tickets']['table']['group'] = t('Zendesk tickets');
  $data['zendesk_tickets']['table']['base'] = [
    'title' => t('Zendesk tickets'),
    'help' => t('Tickets from the Zendesk instance.'),
    'query_id' => 'zendesk_tickets',
  ];

  // Fields.
  $data['zendesk_tickets']['ticket_id'] = [
    'title' => t('Zendesk ticket ID'),
    'help' => t('The ID of the associated Zendesk ticket.'),
    'field' => [
      'id' => 'numeric',
    ],
  ];
  $data['zendesk_tickets']['report_date'] = [
    'title' => t('Report date'),
    'help' => t('The date the report was submitted.'),
    'field' => [
      'id' => 'date',
    ],
  ];
  $data['zendesk_tickets']['resolved_date'] = [
    'title' => t('Report resolved date'),
    'help' => t('The date the report was resolved, if any.'),
    'field' => [
      'id' => 'date',
    ],
  ];
  $data['zendesk_tickets']['report_location_lat'] = [
    'title' => t('Report location latitude'),
    'help' => t('The latitude coordinate of the ticket.'),
    'field' => [
      'id' => 'numeric',
    ],
  ];
  $data['zendesk_tickets']['report_location_lon'] = [
    'title' => t('Report location longitude'),
    'help' => t('The longitude coordinate of the ticket.'),
    'field' => [
      'id' => 'numeric',
    ],
  ];
  $data['zendesk_tickets']['report_status'] = [
    'title' => t('Report status'),
    'help' => t('The status of the ticket.'),
    'field' => [
      'id' => 'standard',
    ],
  ];

  return $data;
}

