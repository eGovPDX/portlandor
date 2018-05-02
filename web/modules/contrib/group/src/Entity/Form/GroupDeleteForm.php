<?php

namespace Drupal\group\Entity\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a group.
 */
class GroupDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the group %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelURL() {
    return new Url('entity.group.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->delete();

    $t_args = [
      '@type' => $this->entity->bundle(),
      '%title' => $this->entity->label(),
    ];

    \Drupal::logger('group')->notice('@type: deleted %title.', $t_args);
    drupal_set_message($this->t('@type %title has been deleted.', $t_args));

    $form_state->setRedirect('entity.group.collection');
  }

}
