<?php

namespace Drupal\portland_relations\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class RelationTypeForm.
 */
class RelationTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $relation_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $relation_type->label(),
      '#description' => $this->t("Label for the Relation type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $relation_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\portland_relations\Entity\RelationType::load',
      ],
      '#disabled' => !$relation_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $relation_type = $this->entity;
    $status = $relation_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Relation type.', [
          '%label' => $relation_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Relation type.', [
          '%label' => $relation_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($relation_type->toUrl('collection'));
  }

}
