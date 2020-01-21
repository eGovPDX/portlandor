<?php

namespace Drupal\portland\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;

/**
 * Copy shortname into group_path
 *
 * @Action(
 *   id = "portland_copy_shortname",
 *   label = @Translation("Copy shortname into group_path (custom action)"),
 *   type = "",
 *   confirm = TRUE,
 * )
 */
class CopyShortnameAction extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->field_group_path->value = $entity->field_shortname_or_acronym->value;
    $entity->save();
    // Don't return anything for a default completion message, otherwise return translatable markup.
    return $this->t('Bulk operation: shortname copied to group_path');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return TRUE;
  }
}
