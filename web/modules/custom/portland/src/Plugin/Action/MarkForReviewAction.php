<?php

namespace Drupal\portland\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Some description.
 *
 * @Action(
 *   id = "mark_for_review_action",
 *   label = @Translation("Mark for review (custom action)"),
 *   type = "",
 *   confirm = FALSE,
 * )
 */
class MarkForReviewAction extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    // This is a required field in the form below. So it will not be NULL.
    $reviewer_uid = $this->configuration['reviewer'];
    $user_display_name = \Drupal\user\Entity\User::load($reviewer_uid)->getDisplayName();

    $entity->status->value = 0;
    $entity->moderation_state->value = 'review';
    $entity->field_reviewer->entity = $reviewer_uid;
    $entity->setNewRevision(TRUE);
    $entity->revision_log = 'Bulk operation: assigned to '. $user_display_name .' for review';
    $entity->setRevisionUserId(\Drupal::currentUser()->id());
    $entity->setRevisionCreationTime(REQUEST_TIME);
    $entity->save();
    // Don't return anything for a default completion message, otherwise return translatable markup.
    return $this->t('Bulk operation: assigned to '. $user_display_name .' for review');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object->getEntityType() === 'node') {
      $access = $object->access('update', $account, TRUE)
        ->andIf($object->status->access('edit', $account, TRUE));
      return $return_as_object ? $access : $access->isAllowed();
    }

    // Other entity types may have different
    // access methods and properties.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['reviewer'] = [
      '#title' => ('Choose a reviewer'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#required' => TRUE,
      '#selection_settings' => [
        'include_anonymous' => FALSE,
        'filter' => [
          // 'role' => ['sales'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['reviewer'] = $form_state->getValue('reviewer');
  }
}
