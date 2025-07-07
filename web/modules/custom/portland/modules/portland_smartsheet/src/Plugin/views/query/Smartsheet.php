<?php

namespace Drupal\portland_smartsheet\Plugin\views\query;

use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\portland_smartsheet\Client\SmartsheetClient;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

enum SmartsheetPagingStrategy: string {
  /**
   * Uses the built-in pagination from the Smartsheet API.
   * (more performant, but sort is per-page rather than global, and filters cannot be dynamic)
   */
  case API = 'api';
  /**
   * Loads all rows from the sheet and builds pages in memory.
   * (less performant, but allows for global sort and dynamic filters)
   * NOTE: limited to 10,000 rows.
   */
  case IN_MEMORY = 'in_memory';
}

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
  private $filters = [];
  private $sorts = [];
  private $whereRowId = [];

  /**
   * {@inheritdoc}
   */
  public function build(ViewExecutable $view) {
    $view->initPager();
  }

  private function shouldFilterOutRow(array $row) {
    // Filter according to any added filter plugins
    foreach ($this->filters as $column_id => $filter) {
      $value = $row[$column_id]->displayValue ?? $row[$column_id]->value ?? NULL;
      switch ($filter['op']) {
        case 'equals':
          if ($value != $filter['value']) return true;
          break;
        default:
          return false; // Keep the row by default if op not found
      }
    }

    return false;
  }

  private function doSort(&$rows) {
    if (empty($this->sorts)) return;

    // Sort according to any added sort plugins
    $multisort_args = [];
    foreach ($this->sorts as $column_id => $order) {
      $column = array_map(fn($el) => $el->displayValue ?? $el->value ?? NULL, array_column($rows, $column_id));

      $multisort_args[] = $column;
      $multisort_args[] = $order;
    }

    $multisort_args[] = &$rows;
    array_multisort(...$multisort_args);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view) {
    $paging_strategy = SmartsheetPagingStrategy::from($this->options['paging_strategy']);
    $sheet_id = $this->options['sheet_id'];
    if ($sheet_id === "") return;

    try {
      $client = new SmartsheetClient($sheet_id);
      $current_page = $view->pager->getCurrentPage();
      $items_per_page = $view->pager->getItemsPerPage();
      $sheet = $client->getSheet([
        'exclude' => 'filteredOutRows',
        'filterId' => $this->options['filter_id'],
        'include' => 'attachments',
        // smartsheet pages are 1-indexed
        'page' => $paging_strategy === SmartsheetPagingStrategy::IN_MEMORY ? 1 : $current_page + 1,
        // if in-mem, load API max of 10,000 rows
        'pageSize' => ($items_per_page === 0 || $paging_strategy === SmartsheetPagingStrategy::IN_MEMORY) ? 10000 : $items_per_page,
        'rowIds' => join(',', $this->whereRowId),
      ]);
      $pager_offset = $view->pager->getOffset();
      $sheet_raw_rows = $sheet->rows;
      // only apply offset if set using API paging. if in-memory, it's applied after filtering/sorting
      if ($pager_offset !== 0 && $paging_strategy === SmartsheetPagingStrategy::API) {
        array_splice($sheet_raw_rows, 0, $pager_offset);
      }

      $rows = [];
      foreach ($sheet_raw_rows as $sheet_row) {
        $row_to_add = array_column($sheet_row->cells, NULL, 'columnId');

        // add special '_data' column with the row metadata
        unset($sheet_row->cells);
        $row_to_add['_data'] = $sheet_row;

        if (!$this->shouldFilterOutRow($row_to_add)) $rows[] = $row_to_add;
      }

      $this->doSort($rows);

      if (empty($this->whereRowId) && $paging_strategy === SmartsheetPagingStrategy::API) {
        $view->total_rows = $sheet->filteredRowCount ?? $sheet->totalRowCount;
      } else {
        // If a rowIds filter is applied in the query string, or we use in-memory paging, the totalRowCount will be incorrect, so do a manual count
        $view->total_rows = count($rows);
      }

      $view->total_rows -= $pager_offset;

      // if using in-memory pagination, apply paging after filtering
      if ($paging_strategy === SmartsheetPagingStrategy::IN_MEMORY) {
        $rows = array_slice($rows, $items_per_page * $current_page + $pager_offset, $items_per_page);
      }

      // Add final filtered/sorted rows to view
      $view->result = array_map(
        fn($k, $v) => new ResultRow([
          'index' => $k,
          'cells' => $v,
        ]),
        array_keys($rows),
        $rows
      );

      $view->pager->total_items = $view->total_rows;
      $view->pager->updatePageInfo();
    } catch (\Exception $e) {
      return;
    }
  }

  public function addFilter(int $column_id, string $op, string|int $value) {
    $this->filters[$column_id] = [
      'op' => $op,
      'value' => $value,
    ];
  }

  public function addSort(int $column_id, string $direction) {
    $this->sorts[$column_id] = strtolower($direction) === 'asc' ? SORT_ASC : SORT_DESC;
  }

  public function addWhereRowId($row_id) {
    $this->whereRowId[] = $row_id;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['filter_id'] = ['default' => ''];
    $options['paging_strategy'] = [
      'default' => SmartsheetPagingStrategy::API->value,
    ];
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
        'event' => 'change',
      ],
    ];
    $form['filter_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter to use'),
      '#default_value' => $this->options['filter_id'],
      '#description' => $this->t('Smartsheet filter to use for query'),
      '#options' => self::getSheetFilters($this->options['sheet_id']),
    ];
    $form['paging_strategy'] = [
      '#type' => 'radios',
      '#title' => $this->t('Paging Strategy'),
      '#default_value' => $this->options['paging_strategy'],
      '#options' => [
        SmartsheetPagingStrategy::API->value => $this->t('API'),
        SmartsheetPagingStrategy::IN_MEMORY->value => $this->t('In Memory'),
      ],
      SmartsheetPagingStrategy::API->value => [
        '#description' => '<strong>Recommended for simple views with no filtering or sorting.</strong> Uses the built-in pagination from the Smartsheet API. More performant, but sort is per-page rather than global, and filters cannot be dynamic.',
      ],
      SmartsheetPagingStrategy::IN_MEMORY->value => [
        '#description' => '<strong>Required for views with column-based filters or global sorting.</strong> Loads all rows from the sheet and builds pages in memory. Less performant, but allows for global sort and dynamic filters. <strong>NOTE: limited to 10,000 rows.</strong>',
      ],
    ];

    parent::buildOptionsForm($form, $form_state);
  }
}
