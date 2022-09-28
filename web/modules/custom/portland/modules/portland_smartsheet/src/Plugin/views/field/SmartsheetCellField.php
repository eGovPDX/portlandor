<?php

namespace Drupal\portland_smartsheet\Plugin\views\field;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\portland_smartsheet\Client\SmartsheetClient;

/**
 * Provide a Smartsheet cell data field on Smartsheet views
 *
 * @ViewsField("smartsheet_cell")
 */
class SmartsheetCellField extends FieldPluginBase {
  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // do nothing -- to override the parent query.
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
      '#description' => $this->t('The column ID from which to show the cell data'),
      '#default_value' => $this->options['column_id'],
      '#options' => $options,
      '#required' => TRUE
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $row, $field = NULL) {
    $cell = $row->cells[$this->options['column_id']];
    return $cell->displayValue ?? $cell->value ?? NULL;
  }
}
