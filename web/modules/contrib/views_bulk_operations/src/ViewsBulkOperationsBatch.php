<?php

namespace Drupal\views_bulk_operations;

use Drupal\Core\Url;

/**
 * Defines module Batch API methods.
 */
class ViewsBulkOperationsBatch {

  /**
   * Translation function wrapper.
   *
   * @see \Drupal\Core\StringTranslation\TranslationInterface:translate()
   */
  public static function t($string, array $args = [], array $options = []) {
    return \Drupal::translation()->translate($string, $args, $options);
  }

  /**
   * Set message function wrapper.
   *
   * @see \drupal_set_message()
   */
  public static function message($message = NULL, $type = 'status', $repeat = TRUE) {
    drupal_set_message($message, $type, $repeat);
  }

  /**
   * Gets the list of entities to process.
   *
   * Used in "all results" batch operation.
   *
   * @param array $data
   *   Processed view data.
   * @param array $context
   *   Batch context.
   */
  public static function getList(array $data, array &$context) {
    // Initialize batch.
    if (empty($context['sandbox'])) {
      $context['sandbox']['processed'] = 0;
      $context['sandbox']['page'] = 0;
      $context['results'] = $data;
    }

    $actionProcessor = \Drupal::service('views_bulk_operations.processor');
    $actionProcessor->initialize($data);

    // Populate queue.
    $list = $actionProcessor->getPageList($context['sandbox']['page']);
    $count = count($list);

    if ($count) {
      foreach ($list as $item) {
        $context['results']['list'][] = $item;
      }

      $context['sandbox']['page']++;
      $context['sandbox']['processed'] += $count;

      // There may be cases where we don't know the total number of
      // results (e.g. mini pager with a search_api view)
      $context['finished'] = 0;
      if ($data['total_results']) {
        $context['finished'] = $context['sandbox']['processed'] / $data['total_results'];
        $context['message'] = static::t('Prepared @count of @total entities for processing.', [
          '@count' => $context['sandbox']['processed'],
          '@total' => $data['total_results'],
        ]);
      }
      else {
        $context['message'] = static::t('Prepared @count entities for processing.', [
          '@count' => $context['sandbox']['processed'],
        ]);
      }
    }

  }

  /**
   * Save generated list to user tempstore.
   *
   * @param bool $success
   *   Was the process successfull?
   * @param array $results
   *   Batch process results array.
   * @param array $operations
   *   Performed operations array.
   */
  public static function saveList($success, array $results, array $operations) {
    if ($success) {
      $results['redirect_url'] = $results['redirect_after_processing'];
      unset($results['redirect_after_processing']);
      $tempstore_factory = \Drupal::service('user.private_tempstore');
      $current_user = \Drupal::service('current_user');
      $tempstore_name = 'views_bulk_operations_' . $results['view_id'] . '_' . $results['display_id'];
      $results['prepopulated'] = TRUE;
      $tempstore_factory->get($tempstore_name)->set($current_user->id(), $results);
    }
  }

  /**
   * Batch operation callback.
   *
   * @param array $data
   *   Processed view data.
   * @param array $context
   *   Batch context.
   */
  public static function operation(array $data, array &$context) {
    // Initialize batch.
    if (empty($context['sandbox'])) {
      $context['sandbox']['processed'] = 0;
      $context['results']['operations'] = [];
    }

    // Get entities to process.
    $actionProcessor = \Drupal::service('views_bulk_operations.processor');
    $actionProcessor->initialize($data);

    // Do the processing.
    $count = $actionProcessor->populateQueue($data['list'], $context);
    if ($count) {
      $batch_results = $actionProcessor->process();
      if (!empty($batch_results)) {
        // Convert translatable markup to strings in order to allow
        // correct operation of array_count_values function.
        foreach ($batch_results as $result) {
          $context['results']['operations'][] = (string) $result;
        }
      }
      $context['sandbox']['processed'] += $count;

      $context['finished'] = 0;
      // There may be cases where we don't know the total number of
      // results (probably all of them were already eliminated but
      // leaving this code just in case).
      if ($context['sandbox']['total']) {
        $context['finished'] = $context['sandbox']['processed'] / $context['sandbox']['total'];
        $context['message'] = static::t('Processed @count of @total entities.', [
          '@count' => $context['sandbox']['processed'],
          '@total' => $context['sandbox']['total'],
        ]);
      }
      else {
        $context['message'] = static::t('Processed @count entities.', [
          '@count' => $context['sandbox']['processed'],
        ]);
      }
    }
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Was the process successfull?
   * @param array $results
   *   Batch process results array.
   * @param array $operations
   *   Performed operations array.
   */
  public static function finished($success, array $results, array $operations) {
    if ($success) {
      $operations = array_count_values($results['operations']);
      $details = [];
      foreach ($operations as $op => $count) {
        $details[] = $op . ' (' . $count . ')';
      }
      $message = static::t('Action processing results: @operations.', [
        '@operations' => implode(', ', $details),
      ]);
      static::message($message);
    }
    else {
      $message = static::t('Finished with an error.');
      static::message($message, 'error');
    }
  }

  /**
   * Batch builder function.
   *
   * @param array $view_data
   *   Processed view data.
   */
  public static function getBatch(array &$view_data) {
    $current_class = get_called_class();

    // Prepopulate results.
    if (empty($view_data['list'])) {
      // Redirect this batch to the processing URL and set
      // previous redirect under a different key for later use.
      $view_data['redirect_after_processing'] = $view_data['redirect_url'];
      $view_data['redirect_url'] = Url::fromRoute('views_bulk_operations.execute_batch', [
        'view_id' => $view_data['view_id'],
        'display_id' => $view_data['display_id'],
      ]);

      $batch = [
        'title' => static::t('Prepopulating entity list for processing.'),
        'operations' => [
          [
            [$current_class, 'getList'],
            [$view_data],
          ],
        ],
        'progress_message' => static::t('Prepopulating, estimated time left: @estimate, elapsed: @elapsed.'),
        'finished' => [$current_class, 'saveList'],
      ];
    }

    // Execute action.
    else {
      $batch = [
        'title' => static::t('Performing @operation on selected entities.', ['@operation' => $view_data['action_label']]),
        'operations' => [
          [
            [$current_class, 'operation'],
            [$view_data],
          ],
        ],
        'progress_message' => static::t('Processing, estimated time left: @estimate, elapsed: @elapsed.'),
        'finished' => [$current_class, 'finished'],
      ];
    }

    return $batch;
  }

}
