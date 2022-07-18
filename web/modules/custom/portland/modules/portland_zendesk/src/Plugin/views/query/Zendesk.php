<?php

namespace Drupal\portland_zendesk\Plugin\views\query;

use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\portland_zendesk\Client\ZendeskClient;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;

/**
 * Zendesk views query plugin which wraps calls to the Zendesk Tickets API in order to
 * expose the results to views.
 *
 * @ViewsQuery(
 *   id = "zendesk",
 *   title = @Translation("Zendesk"),
 *   help = @Translation("Query against the Zendeks Tickets API.")
 * )
 */
class Zendesk extends QueryPluginBase {

  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view) {

    $client = new ZendeskClient();

    $query = $view->query->options['ticket_query'];

    $response = $client->search()->find($query, ['sort_by' => 'updated_at']);
    $tickets = $response->results;
    $index = 0;

    foreach($tickets as $ticket) {
      $row['ticket_id'] = $ticket->id;
      $row['ticket_status'] = $ticket->status;
      $row['ticket_subject'] = $ticket->subject;
      $row['ticket_description'] = $ticket->description;
      $row['ticket_priority'] = $ticket->priority;
      $row['ticket_created_date'] = date("U", strtotime($ticket->created_at));
      $row['ticket_updated_date'] = date("U", strtotime($ticket->updated_at));

      $row['custom_location_lat'] = array_column($ticket->custom_fields, null, 'id')['5581480390679']->value;
      $row['custom_location_lon'] = array_column($ticket->custom_fields, null, 'id')['5581490332439']->value;
      $row['custom_address'] = array_column($ticket->custom_fields, null, 'id')['1500012743961']->value;
      $row['custom_graffiti_description'] = array_column($ticket->custom_fields, null, 'id')['7557381052311']->value;      

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

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['ticket_query'] = ['default' => 'type:ticket status:open form:6499767163543'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['ticket_query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zendesk Search API query string'),
      '#default_value' => $this->options['ticket_query'],
      '#description' => $this->t('Use the Zendesk Search API query string needed to display the desired results. This query is used in place of view filters. Example: "type:ticket status:open form:6499767163543"'),
    ];
    parent::buildOptionsForm($form, $form_state);
  }



}