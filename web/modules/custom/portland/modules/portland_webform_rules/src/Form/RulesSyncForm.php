<?php

namespace Drupal\portland_webform_rules\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\portland_webform_rules\Service\RulesSyncService;

class RulesSyncForm extends FormBase {

  protected $syncService;

  public function __construct(RulesSyncService $sync_service) {
    $this->syncService = $sync_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('portland_webform_rules.rules_sync')
    );
  }

  public function getFormId() {
    return 'portland_webform_rules_sync_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#markup' => '<p>Click the button below to sync the Smartsheet rules into Drupal.</p>',
    ];

    $form['sync'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sync Now'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->syncService->sync();
    $this->messenger()->addStatus($this->t('Rules synced successfully.'));
  }
}

