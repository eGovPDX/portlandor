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
 *   label = @Translation("Mark for review"),
 *   type = "node",
 *   confirm = TRUE,
 * )
 */
class MarkForReviewAction extends ViewsBulkOperationsActionBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    // Load the latest revision
    $latestNode = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
                  ->latestRevision()
                  ->condition('nid', $entity->id())
                  ->execute();
    reset($latestNode);
    $latestRevisionId = key($latestNode);
    $latestRev = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($latestRevisionId);

    $latestRev->set('status', 0);
    $latestRev->set('moderation_state', 'review');
    $latestRev->field_reviewer->entity = $this->configuration['reviewer'];

    // Make this change a new revision
    $latestRev->setNewRevision(TRUE);
    $latestRev->revision_log = 'Mark for review';
    $latestRev->setRevisionCreationTime(REQUEST_TIME);
    $latestRev->setRevisionUserId(\Drupal::currentUser()->id());
    $latestRev->save();

    // Don't return anything for a default completion message, otherwise return translatable markup.
    return $this->t('Marked for review');
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