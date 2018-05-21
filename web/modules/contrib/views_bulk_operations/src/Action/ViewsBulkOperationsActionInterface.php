<?php

namespace Drupal\views_bulk_operations\Action;

use Drupal\views\ViewExecutable;

/**
 * Defines Views Bulk Operations action interface.
 */
interface ViewsBulkOperationsActionInterface {

  /**
   * Set action context.
   *
   * Implementation should have an option to add data to the
   * context, not overwrite it on every method execution.
   *
   * @param array $context
   *   The context array.
   *
   * @see ViewsBulkOperationsActionBase::setContext
   */
  public function setContext(array &$context);

  /**
   * Set view object.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The processed view.
   */
  public function setView(ViewExecutable $view);

  /**
   * Execute action on multiple entities.
   *
   * Can return an array of results of processing, if no return value
   * is provided, action label will be used for each result.
   *
   * @param array $objects
   *   An array of entities.
   *
   * @return array
   *   An array of translatable markup objects or strings (optional)
   */
  public function executeMultiple(array $objects);

}
