<?php

namespace Drupal\views_bulk_operations\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Delete entity action with default confirmation form.
 *
 * @Action(
 *   id = "views_bulk_operations_delete_entity",
 *   label = @Translation("Delete selected entities"),
 *   type = "",
 *   confirm = TRUE,
 * )
 */
class EntityDeleteAction extends ViewsBulkOperationsActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->delete();
    return $this->t('Delete entities');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = $object->access('delete', $account, TRUE);
    if ($object->getEntityType() === 'node') {
      $access->andIf($object->status->access('delete', $account, TRUE));
    }
    return $return_as_object ? $access : $access->isAllowed();
  }

}
