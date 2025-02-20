<?php

namespace Drupal\portland_maps\Plugin\views\query;

use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\portland_maps\Client\PortlandMapsClient;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;

/**
 * PortlandMaps views query plugin which exposes data from the PortlandMaps API.
 *
 * @ViewsQuery(
 *   id = "portland_maps",
 *   title = @Translation("Portland Maps"),
 *   help = @Translation("Query property data from the Portland Maps API.")
 * )
 */
class PortlandMaps extends QueryPluginBase
{

  public function execute(ViewExecutable $view)
  {
    // Initialize index for tracking rows.
    $index = 0;

    // Get contextual argument for detail_id from Views.
    $detail_id = $view->args[0] ?? NULL;  // Contextual filter comes from the first argument

    // Create a new instance of the PortlandMapsClient.
    $client = new PortlandMapsClient();

    // If a detail_id is provided, fetch that specific record; otherwise, fetch all mock data.
    if ($detail_id) {
      $data = [$client->getDetail($detail_id)];
    } else {
      $data = $client->getDetail('R259621');  // Default mock data
    }

    // Loop through each property and add it to the view.
    foreach ($data as $property) {
      $row = [
        'owner_name' => $property['owner']['name'] ?? '',
        'owner_address' => $property['owner']['address'] ?? '',
        'address' => $property['general']['address'] ?? '',
        'address2' => $property['general']['address2'] ?? '',
        'block' => $property['general']['block'] ?? '',
        'city' => $property['general']['city'] ?? '',
        'property_id' => $property['general']['property_id'] ?? '',
        'tax_roll' => $property['general']['tax_roll'] ?? '',
        'use' => $property['general']['use'] ?? '',
        'lot' => $property['general']['lot'] ?? '',
        'county' => $property['general']['county'] ?? '',
        'state_id' => $property['general']['state_id'] ?? '',
        'new_state_id' => $property['general']['new_state_id'] ?? '',
        'parent_state_id' => $property['general']['parent_state_id'] ?? '',
        'alt_account_number' => $property['general']['alt_account_number'] ?? '',
        'map_number' => $property['general']['map_number'] ?? '',
        'tax_code' => $property['general']['tax_code'] ?? '',
        'x' => $property['general']['x'] ?? '',
        'y' => $property['general']['y'] ?? '',
        'index' => $index,
      ];

      $index++;
      $view->result[] = new ResultRow($row);
    }
  }

  // Ensure we handle contextual filters manually
  public function ensureArgument(ArgumentPluginBase $argument, $group)
  {
    // Override the default Views behavior to prevent SQL filtering
    return;
  }

  /**
   * Prevent Views from applying SQL-based filtering.
   */
  public function addWhere($group, $field = NULL, $value = NULL, $operator = NULL)
  {
    // Do nothing, since we are not using SQL queries.
    return;
  }

  /**
   * Prevent Views from trying to process SQL-based arguments.
   */
  public function addArguments($arguments)
  {
    // Do nothing to avoid errors from Views expecting SQL filtering.
    return;
  }


  public function ensureTable($table, $relationship = NULL)
  {
    return '';
  }

  public function addField($table, $field, $alias = '', $params = array())
  {
    return $field;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions()
  {
    $options = parent::defineOptions();
    $options['test_option'] = ['default' => 'This is a test, do not use.'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state)
  {
    $form['ticket_query'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Test option'),
      '#default_value' => $this->options['test_option'],
      '#description' => $this->t('This is a test option for the Portland Maps query plugin.'),
    ];
    parent::buildOptionsForm($form, $form_state);
  }
}
