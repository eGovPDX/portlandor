<?php

namespace Drupal\views_bulk_operations_test\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Action for test purposes only.
 *
 * @Action(
 *   id = "views_bulk_operations_passing_test_action",
 *   label = @Translation("VBO parameters passing test action"),
 *   type = "node",
 * )
 */
class ViewsBulkOperationsPassTestAction extends ViewsBulkOperationsActionBase {

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $nodes) {
    if (!empty($this->context['sandbox'])) {
      drupal_set_message(sprintf(
        'Processed %s of %s.',
        $this->context['sandbox']['processed'],
        $this->context['sandbox']['total']
      ));
    }

    // Check if the passed view result rows contain the correct nodes.
    if (empty($this->context['sandbox']['result_pass_error'])) {
      $this->view->result = array_values($this->view->result);
      foreach ($nodes as $index => $node) {
        $result_node = $this->view->result[$index]->_entity;
        if (
          $node->id() !== $result_node->id() ||
          $node->label() !== $result_node->label()
        ) {
          $this->context['sandbox']['result_pass_error'] = TRUE;
        }
      }
    }

    $batch_size = isset($this->context['sandbox']['batch_size']) ? $this->context['sandbox']['batch_size'] : 0;
    $total = isset($this->context['sandbox']['total']) ? $this->context['sandbox']['total'] : 0;
    $processed = isset($this->context['sandbox']['processed']) ? $this->context['sandbox']['processed'] : 0;

    // On last batch display message if passed rows match.
    if ($processed + $batch_size >= $total) {
      if (empty($this->context['sandbox']['result_pass_error'])) {
        drupal_set_message('Passed view results match the entity queue.');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->executeMultiple([$entity]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }

}
