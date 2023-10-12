<?php

namespace Drupal\portland_smartsheet\Plugin\views\sort;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\views\ResultRow;
use Drupal\portland_smartsheet\Client\SmartsheetClient;

/**
 * Provide a Smartsheet cell data sort plugin on Smartsheet views
 *
 * @ViewsSort("smartsheet_cell")
 */
class SmartsheetCellSort extends SortPluginBase {
  /**
   * Called to add the sort to a query.
   */
  public function query() {
    $this->query->addSort($this->options['column_id'], $this->options['order']);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['column_id'] = ['default' => ''];
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
      '#title' => $this->t('Column ID'),
      '#description' => $this->t('The column ID from which to sort the rows'),
      '#default_value' => $this->options['column_id'],
      '#options' => $options,
      '#required' => true
    ];
  }
}
