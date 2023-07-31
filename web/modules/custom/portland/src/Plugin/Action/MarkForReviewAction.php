<?php

namespace Drupal\portland\Plugin\Action;

use Drupal\user\Entity\User;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Access\AccessResult;

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
    $user_display_name = User::load($reviewer_uid)->getDisplayName();

    // The machine name of the review state for some workflows is "review", whereas others use "in_review" 
    // so we need to figure out the correct state name depending on the entity's associated workflow.

    // Get workflow associated with the entity bundle
    $bundle = $entity->bundle();
    $query = \Drupal::entityTypeManager()->getStorage('workflow')->getQuery();
    $query->condition('type_settings.entity_types.node.*', $bundle);
    $workflow = $query->execute();
    $workflow = array_shift( $workflow );

    // Get the workflow's states and set the entity's moderation state to either
    // 'review' or 'in_review' depending on correct workflow state name.
    $workflow_states = \Drupal::config("workflows.workflow." . $workflow)->get("type_settings.states");
    $entity->moderation_state->value = array_key_exists('review', $workflow_states) ? 'review' : 'in_review';

    $entity->status->value = 0;
    // Some entities (e.g. contacts) don't have a reviewer field
    if ($entity->hasField('field_reviewer'))
      $entity->field_reviewer->entity = $reviewer_uid;
    $entity->setNewRevision(TRUE);
    $entity->revision_log = 'Bulk operation: assigned to '. $user_display_name .' for review';
    $entity->setRevisionUserId(\Drupal::currentUser()->id());
    $entity->setRevisionCreationTime(\Drupal::time()->getRequestTime());
    $entity->save();
    // Don't return anything for a default completion message, otherwise return translatable markup.
    return $this->t('Bulk operation: assigned to '. $user_display_name .' for review');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object->getEntityTypeId() === 'node') {
      $access = $object->access('update', $account, TRUE);
      return $return_as_object ? $access : $access->isAllowed();
    }

    // Other entity types may have different
    // access methods and properties.
    return ($return_as_object ? AccessResult::allowed() : true );
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
