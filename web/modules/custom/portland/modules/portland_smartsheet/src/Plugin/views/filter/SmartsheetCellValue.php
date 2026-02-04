<?php

namespace Drupal\portland_smartsheet\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\portland_smartsheet\Client\SmartsheetClient;
use Drupal\portland_smartsheet\Plugin\views\query\SmartsheetPagingStrategy;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Provides a view filter plugin for filtering rows based on column value.
 */
#[ViewsFilter("smartsheet_cell")]
class SmartsheetCellValue extends InOperator {
  private const CACHE_PREFIX = 'portland_smartsheet:cell_filter_options:';

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['column_id'] = ['default' => ''];
    $options['options_cache_ttl_sec'] = ['default' => 60];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $sheet_id = $this->view->getQuery()->options['sheet_id'];
    $options = [];
    try {
      if ($sheet_id === '') return;
      $client = new SmartsheetClient($sheet_id);

      $columns = $client->listAllColumns();
      $options = array_column($columns, 'title', 'id');
    } catch (\Exception $e) {}

    $form['column_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Column'),
      '#description' => $this->t('The column to filter on'),
      '#default_value' => $this->options['column_id'],
      '#options' => $options,
      '#required' => true,
      '#weight' => -99,
    ];
    $form['options_cache_ttl_sec'] = [
      '#type' => 'number',
      '#min' => 5,
      '#step' => 1,
      '#title' => t('Options cache TTL (seconds)'),
      '#default_value' => $this->options['options_cache_ttl_sec'],
      '#required' => TRUE,
      '#description' => t('The time to cache the filter options, in seconds. Finding all unique values in the Smartsheet column is an intensive operation that needs to be cached. Minimum 5 seconds.'),
      '#weight' => -98,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    // pull from instance variable first
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    // if null, then try cache
    $cache = \Drupal::cache();
    $cache_id = self::CACHE_PREFIX . hash('sha256', $this->view->id() . $this->view->current_display);
    $cache_result = $cache->get($cache_id);
    if ($cache_result && isset($cache_result->data)) {
      $this->valueOptions = $cache_result->data;
      return $cache_result->data;
    }

    $this->valueOptions = [];

    try {
      if (empty($this->options['column_id'])) {
        return $this->valueOptions;
      }

      /** @var \Drupal\portland_smartsheet\Plugin\views\query\Smartsheet $query */
      $query = $this->view->getQuery();
      // Use in-memory paging to ensure we get all rows for filter values
      $sheet = $query->getSheet($this->view, SmartsheetPagingStrategy::IN_MEMORY);
      $column_id = $this->options['column_id'];

      // Extract all unique values from the specified column
      $values = [];
      foreach ($sheet->rows as $row) {
        foreach ($row->cells as $cell) {
          if ($cell->columnId == $column_id && !empty($cell->value)) {
            $values[$cell->value] = $cell->value;
          }
        }
      }

      asort($values);
      $this->valueOptions = $values;
      // Cache the options to avoid repeated queries
      $cache->set($cache_id, $values, time() + (int) $this->options['options_cache_ttl_sec']);
    }
    catch (\Exception $e) {
      \Drupal::logger('portland_smartsheet')->error('Error retrieving Smartsheet filter values in view @view_id. Details: @error', [
        '@view_id' => $this->view->id(),
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (!empty($this->value) && !empty($this->options['column_id'])) {
      $operator = $this->operator === 'in' ? 'equals' : 'not_equals';
      foreach ($this->value as $value) {
        $this->query->addFilter((int)$this->options['column_id'], $operator, $value);
      }
    }
  }
}
