<?php

namespace Drupal\masquerade\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\masquerade\Masquerade;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form builder for the masquerade form.
 */
class MasqueradeForm extends FormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The masquerade service.
   *
   * @var \Drupal\masquerade\Masquerade
   */
  protected $masquerade;

  /**
   * Constructs a MasqueradeForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\masquerade\Masquerade $masquerade
   *   The masquerade service.
   */
  public function __construct(EntityManagerInterface $entity_manager, Masquerade $masquerade) {
    $this->entityManager = $entity_manager;
    $this->masquerade = $masquerade;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('masquerade')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'masquerade_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['autocomplete'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['autocomplete']['masquerade_as'] = array(
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#selection_settings' => ['include_anonymous' => FALSE, 'match_operator' => 'STARTS_WITH'],
      '#title' => $this->t('Username'),
      '#title_display' => 'invisible',
      '#required' => TRUE,
      '#placeholder' => $this->t('Masquerade as…'),
      '#size' => '18',
    );
    $form['autocomplete']['actions'] = array('#type' => 'actions');
    $form['autocomplete']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Switch'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $user_id = $form_state->getValue('masquerade_as');
    if (empty($user_id)) {
      $form_state->setErrorByName('masquerade_as', $this->t('The user does not exist. Please enter a valid username.'));
      return;
    }
    $target_account = $this->entityManager
      ->getStorage('user')
      ->load($user_id);
    if ($error = masquerade_switch_user_validate($target_account)) {
      $form_state->setErrorByName('masquerade_as', $error);
    }
    else {
      $form_state->setValue('masquerade_target_account', $target_account);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->masquerade->switchTo($form_state->getValue('masquerade_target_account'));
  }

}
