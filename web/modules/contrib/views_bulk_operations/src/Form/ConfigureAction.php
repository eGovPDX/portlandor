<?php

namespace Drupal\views_bulk_operations\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;

/**
 * Action configuration form.
 */
class ConfigureAction extends FormBase {

  use ViewsBulkOperationsFormTrait;

  /**
   * User private temporary storage factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Views Bulk Operations action manager.
   *
   * @var \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager
   */
  protected $actionManager;

  /**
   * Views Bulk Operations action processor.
   *
   * @var \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface
   */
  protected $actionProcessor;

  /**
   * Constructor.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $tempStoreFactory
   *   User private temporary storage factory.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager $actionManager
   *   Extended action manager object.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface $actionProcessor
   *   Views Bulk Operations action processor.
   */
  public function __construct(
    PrivateTempStoreFactory $tempStoreFactory,
    ViewsBulkOperationsActionManager $actionManager,
    ViewsBulkOperationsActionProcessorInterface $actionProcessor
  ) {
    $this->tempStoreFactory = $tempStoreFactory;
    $this->actionManager = $actionManager;
    $this->actionProcessor = $actionProcessor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('plugin.manager.views_bulk_operations_action'),
      $container->get('views_bulk_operations.processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_bulk_operations_configure_action';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $view_id = NULL, $display_id = NULL) {

    $form_data = $this->getFormData($view_id, $display_id);

    // TODO: display an error msg, redirect back.
    if (!isset($form_data['action_id'])) {
      return;
    }

    $form['#title'] = $this->t('Configure "%action" action applied to the selection', ['%action' => $form_data['action_label']]);

    $selection = [];
    if (!empty($form_data['entity_labels'])) {
      $form['list'] = [
        '#theme' => 'item_list',
        '#items' => $form_data['entity_labels'],
      ];
    }
    else {
      $form['list'] = [
        '#type' => 'item',
        '#markup' => $this->t('All view results'),
      ];
    }
    $form['list']['#title'] = $this->t('Selected @count entities:', ['@count' => $form_data['selected_count']]);

    // :D Make sure the submit button is at the bottom of the form
    // and is editale from the action buildConfigurationForm method.
    $form['actions']['#weight'] = 666;
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
      '#submit' => [
        [$this, 'submitForm'],
      ],
    ];
    $this->addCancelButton($form);

    $action = $this->actionManager->createInstance($form_data['action_id']);

    if (method_exists($action, 'setContext')) {
      $action->setContext($form_data);
    }

    $form_state->set('views_bulk_operations', $form_data);
    $form = $action->buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_data = $form_state->get('views_bulk_operations');

    $action = $this->actionManager->createInstance($form_data['action_id']);
    if (method_exists($action, 'validateConfigurationForm')) {
      $action->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_data = $form_state->get('views_bulk_operations');

    $action = $this->actionManager->createInstance($form_data['action_id']);
    if (method_exists($action, 'submitConfigurationForm')) {
      $action->submitConfigurationForm($form, $form_state);
      $form_data['configuration'] = $action->getConfiguration();
    }
    else {
      $form_state->cleanValues();
      $form_data['configuration'] = $form_state->getValues();
    }

    $definition = $this->actionManager->getDefinition($form_data['action_id']);
    if (!empty($definition['confirm_form_route_name'])) {
      // Update tempStore data.
      $this->setTempstoreData($form_data, $form_data['view_id'], $form_data['display_id']);
      // Go to the confirm route.
      $form_state->setRedirect($definition['confirm_form_route_name'], [
        'view_id' => $form_data['view_id'],
        'display_id' => $form_data['display_id'],
      ]);
    }
    else {
      $this->deleteTempstoreData($form_data['view_id'], $form_data['display_id']);
      $this->actionProcessor->executeProcessing($form_data);
      $form_state->setRedirectUrl($form_data['redirect_url']);
    }
  }

}
