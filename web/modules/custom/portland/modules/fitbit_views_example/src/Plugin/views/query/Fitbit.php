<?php

namespace Drupal\fitbit_views_example\Plugin\views\query;

use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\portland_zendesk\Client\ZendeskClient;
use Drupal\views\ResultRow;

/**
 * Fitbit views query plugin which wraps calls to the Fitbit API in order to
 * expose the results to views.
 *
 * @ViewsQuery(
 *   id = "fitbit",
 *   title = @Translation("Fitbit"),
 *   help = @Translation("Query against the Fitbit API.")
 * )
 */
class Fitbit extends QueryPluginBase {

  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view) {

    // if ($access_tokens = $this->fitbitAccessTokenManager->loadMultipleAccessToken()) {
    //   $index = 0;
    //   foreach ($access_tokens as $uid => $access_token) {
    //     if ($data = $this->fitbitClient->getResourceOwner($access_token)) {
    //       $data = $data->toArray();
    //       $row['display_name'] = $data['displayName'];
    //       $row['average_daily_steps'] = $data['averageDailySteps'];
    //       $row['avatar'] = $data['avatar'];
    //       $row['height'] = $data['height'];
    //       // 'index' key is required.
    //       $row['index'] = $index++;
    //       $view->result[] = new ResultRow($row);
    //     }
    //   }
    // }

    $client = new ZendeskClient();
    // $response_tickets = $client->tickets()->findAll();
    // $response_tickets = $client->tickets()->findAll(['form' => '6499767163543']);

    $response = $client->search()->find('type:ticket status:open form:6499767163543', ['sort_by' => 'updated_at']);
    $tickets = $response->results;
    $index = 0;

    foreach($tickets as $ticket) {
      $row['ticket_id'] = $ticket->id;
      $row['ticket_status'] = $ticket->status;
      $row['ticket_subject'] = $ticket->subject;
      $row['ticket_description'] = $ticket->description;
      $row['ticket_priority'] = $ticket->priority;
      $row['ticket_created_date'] = $ticket->created_at;
      $row['ticket_updated_date'] = $ticket->updated_at;

      $row['custom_location_lat'] = array_column($ticket->custom_fields, null, 'id')['5581480390679']->value;
      $row['custom_location_lon'] = array_column($ticket->custom_fields, null, 'id')['5581490332439']->value;
      $row['custom_address'] = array_column($ticket->custom_fields, null, 'id')['1500012743961']->value;

      $row['index'] = $index;
      $index = $index + 1;
      
      $view->result[] = new ResultRow($row);
    }
  }

  public function ensureTable($table, $relationship = NULL) {
    return '';
  }

  public function addField($table, $field, $alias = '', $params = array()) {
    return $field;
  }

}