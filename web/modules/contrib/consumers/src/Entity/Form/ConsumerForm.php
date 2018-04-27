<?php

namespace Drupal\consumers\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;

/**
 * Form controller for Consumer edit forms.
 */
class ConsumerForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\consumers\Entity\Consumer */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $form['langcode'] = [
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
      '#weight' => -5,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();
    $label = $this->entity->label();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Consumer.', [
          '%label' => $label,
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Consumer.', [
          '%label' => $label,
        ]));
    }
    $form_state->setRedirect('entity.consumer.collection');
  }

}
