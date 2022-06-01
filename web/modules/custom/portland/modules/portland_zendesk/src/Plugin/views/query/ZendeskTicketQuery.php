<?php

namespace Drupal\portland_zendesk\Plugin\views\query;

use Drupal\views\Plugin\views\query\QueryPluginBase;

/**
 * Fitbit views query plugin which wraps calls to the Fitbit API in order to
 * expose the results to views.
 *
 * @ViewsQuery(
 *   id = "zendesk_ticket_query",
 *   title = @Translation("Zendesk Ticket Query"),
 *   help = @Translation("Query against the Zendesk API to get ticket data.")
 * )
 */
class ZendeskTicketQuery extends QueryPluginBase {

  /**
   * ZendeskTicketQuery constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param ZendeskClient $zendesk_client
   * @param FitbitAccessTokenManager $fitbit_access_token_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ZendeskClient $zendesk_client, ZendeskAccessTokenManager $zendesk_access_token_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->zendeskClient = $zendesk_client;
    $this->zendeskAccessTokenManager = $zendesk_access_token_manager;
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
  public function execute(ViewExecutable $view) {
    if ($access_tokens = $this->zendeskAccessTokenManager->loadMultipleAccessToken()) {
      $index = 0;
      foreach ($access_tokens as $uid => $access_token) {
        if ($data = $this->zendeskClient->getResourceOwner($access_token)) {
          $data = $data->toArray();
          $row['display_name'] = $data['displayName'];
          $row['average_daily_steps'] = $data['averageDailySteps'];
          $row['avatar'] = $data['avatar'];
          $row['height'] = $data['height'];
          // 'index' key is required.
          $row['index'] = $index++;
          $view->result[] = new ResultRow($row);
        }
      }
    }
  }

}