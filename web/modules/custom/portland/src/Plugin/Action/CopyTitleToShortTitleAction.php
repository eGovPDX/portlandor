<?php

namespace Drupal\portland\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;

/**
 * Copy Title into Short Title if it's empty
 *
 * @Action(
 *   id = "portland_copy_title_to_short_title",
 *   label = @Translation("Copy Title into Short Title (custom action)"),
 *   type = "",
 *   confirm = TRUE,
 * )
 */
class CopyTitleToShortTitleAction extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if( $entity->hasField('field_menu_link_text') && $entity->field_menu_link_text->value == null) {
      $entity->field_menu_link_text->value = $entity->getTitle();
      $entity->save();
      return $this->t('Title copied to empty Short Title field');
    }
    return $this->t('Title NOT copied because Short Title field is NOT empty or doesn\'t exist');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return TRUE;
  }
}
