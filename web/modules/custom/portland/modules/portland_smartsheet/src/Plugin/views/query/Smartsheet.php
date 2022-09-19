<?php

namespace Drupal\portland_smartsheet\Plugin\views\query;

use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\portland_smartsheet\Client\SmartsheetClient;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Smartsheet views query plugin which exposes sheet columns.
 *
 * @ViewsQuery(
 *   id = "smartsheet",
 *   title = @Translation("Smartsheet"),
 *   help = @Translation("Query from the Smartsheet API")
 * )
 */
class Smartsheet extends QueryPluginBase {
  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view) {
    $sheet_id = $view->query->options['sheet_id'];
    if ($sheet_id === "") return;

    $view->initPager();
    try {
      $client = new SmartsheetClient($sheet_id);
      $sheet = $client->getSheet([
        'exclude' => 'filteredOutRows',
        'filterId' => $this->options['filter_id'],
        'page' => $view->pager->getCurrentPage() + 1,
        'pageSize' => $view->pager->getItemsPerPage()
      ]);
      $sheet_rows = array_slice($sheet->rows, $view->pager->getOffset());
      foreach ($sheet_rows as $index => $sheet_row) {
        $result_row['cells'] = array_column($sheet_row->cells, NULL, 'columnId');
        $result_row['index'] = $index;
        $view->result[] = new ResultRow($result_row);
      }

      $view->pager->total_items = $sheet->totalRowCount;
      $view->pager->updatePageInfo();
    } catch (\Exception $e) {
      return;
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
    $options['filter_id'] = ['default' => ''];
    $options['sheet_id'] = ['default' => ''];
    return $options;
  }

  public static function getSheetInfo($sheet_id) {
    if ($sheet_id === '') return 'No sheet configured';

    try {
      $client = new SmartsheetClient($sheet_id);
      $res = $client->getSheet(['columnIds' => 0, 'rowIds' => 0]);
      $sheet_link = htmlentities($res->permalink);
      $sheet_name = htmlentities($res->name);

      return "<strong>Sheet:</strong> <a href=\"{$sheet_link}\">{$sheet_name}</a>";
    } catch (\Exception $e) {}

    return 'No sheet with that ID found';
  }

  public static function getSheetFilters($sheet_id) {
    if ($sheet_id === '') return ['' => 'No sheet configured'];

    try {
      $client = new SmartsheetClient($sheet_id);
      $filters = $client->listAllFilters();
      $options = array_column($filters, 'name', 'id');
      $options[''] = 'None';

      return $options;
    } catch (\Exception $e) {}

    return ['' => 'Error fetching filters'];
  }

  public static function ajaxFetchSheetInfo(&$form, FormStateInterface $form_state) {
    $sheet_id = $form_state->getValues()['query']['options']['sheet_id'];
    $options_form = $form['options']['query']['options'];
    $options_form['filter_id']['#options'] = self::getSheetFilters($sheet_id);

    $res = new AjaxResponse();
    $res->addCommand(new HtmlCommand('.js-form-item-query-options-sheet-id .form-item__description', self::getSheetInfo($sheet_id)));
    $res->addCommand(new HtmlCommand('.js-form-item-query-options-filter-id', $options_form['filter_id']));

    return $res;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['sheet_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Smartsheet Sheet ID'),
      '#default_value' => $this->options['sheet_id'],
      '#description' => self::getSheetInfo($this->options['sheet_id']),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [static::class, 'ajaxFetchSheetInfo'],
        'event' => 'change'
      ]
    ];
    $form['filter_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter to use'),
      '#default_value' => $this->options['filter_id'],
      '#description' => $this->t('Smartsheet filter to use for query'),
      '#options' => self::getSheetFilters($this->options['sheet_id'])
    ];

    parent::buildOptionsForm($form, $form_state);
  }
}
