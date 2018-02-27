<?php

namespace Drupal\context_ui\Form;

use Drupal\Core\Url;
use Drupal\context\ContextManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityConfirmFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContextDisableForm extends EntityConfirmFormBase {

  /**
   * @var ContextManager
   */
  protected $contextManager;

  /**
   * @param ContextManager $contextManager
   */
  function __construct(ContextManager $contextManager) {
    $this->contextManager = $contextManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('context.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to %status the %label context?', array(
      '%status' => $this->entity->disabled() ? "enable" : "disable",
      '%label' => $this->entity->getLabel(),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action will %status the %label context.', array(
      '%status' => $this->entity->disabled() ? "enable" : "disable",
      '%label' => $this->entity->getLabel(),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.context.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Remove the cancel button if this is an AJAX request since Drupals built
    // in modal dialogues does not handle buttons that are not a primary
    // button very well.
    if ($this->getRequest()->isXmlHttpRequest()) {
      unset($form['actions']['cancel']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    $this->entity->disable();
    drupal_set_message($this->t('The context %title has been %status.', array(
      '%title' => $this->entity->getLabel(),
      '%status' => $this->entity->disabled() ? "disabled" : "enabled",
    )));

    $formState->setRedirectUrl($this->getCancelUrl());
  }
}
