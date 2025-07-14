<?php

namespace Drupal\portland_smartsheet\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\portland_smartsheet\Client\SmartsheetClient;
use Drupal\views\Attribute\ViewsArgument;
use Drupal\views\Plugin\views\argument\StringArgument;

/**
 * Provides a view argument plugin to filter Smartsheet queries by cell value.
 */
#[ViewsArgument("smartsheet_cell")]
class SmartsheetCellValue extends StringArgument {
  /**
   * {@inheritdoc}
   */
  public function query($group_by = false) {
    $this->query->addFilter($this->options['column_id'], 'equals', $this->getValue());
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
      '#title' => $this->t('Column'),
      '#description' => $this->t('The column to filter on'),
      '#default_value' => $this->options['column_id'],
      '#options' => $options,
      '#required' => true,
      '#weight' => -99,
    ];
  }
}
