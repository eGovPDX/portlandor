<?php

namespace Drupal\views_bulk_operations\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Cancels a user account.
 *
 * @Action(
 *   id = "vbo_cancel_user_action",
 *   label = @Translation("Cancel the selected user accounts"),
 *   type = "user",
 * )
 */
class CancelUserAction extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface, PluginFormInterface {

  /**
   * The current user.
   *
   * @var Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * User module config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $userConfig;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Object constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin Id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory object.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountInterface $currentUser,
    ConfigFactoryInterface $configFactory,
    ModuleHandlerInterface $moduleHandler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $currentUser;
    $this->userConfig = $configFactory->get('user.settings');
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    if ($account->id() === $this->currentUser->id() && (empty($this->context['list']) || count($this->context['list'] > 1))) {
      drupal_set_message($this->t('The current user account cannot be canceled in a batch operation. Select your account only or cancel it from your account page.'), 'error');
    }
    elseif (intval($account->id()) === 1) {
      drupal_set_message($this->t('The user 1 account (%label) cannot be canceled.', [
        '%label' => $account->label(),
      ]), 'error');
    }
    else {
      // Allow other modules to act.
      if ($this->configuration['user_cancel_method'] != 'user_cancel_delete') {
        $this->moduleHandler->invokeAll('user_cancel', [
          $this->configuration,
          $account,
          $this->configuration['user_cancel_method'],
        ]);
      }

      // Cancel the account.
      _user_cancel($this->configuration, $account, $this->configuration['user_cancel_method']);

      // If current user was cancelled, logout.
      if ($account->id() == $this->currentUser->id()) {
        _user_cancel_session_regenerate();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['user_cancel_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('When cancelling these accounts'),
    ];

    $form['user_cancel_method'] += user_cancel_methods();

    // Allow to send the account cancellation confirmation mail.
    $form['user_cancel_confirm'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require email confirmation to cancel account'),
      '#default_value' => FALSE,
      '#description' => $this->t('When enabled, the user must confirm the account cancellation via email.'),
    ];
    // Also allow to send account canceled notification mail, if enabled.
    $form['user_cancel_notify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify user when account is canceled'),
      '#default_value' => FALSE,
      '#access' => $this->userConfig->get('notify.status_canceled'),
      '#description' => $this->t('When enabled, the user will receive an email notification after the account has been canceled.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    return $object->access('delete', $account, $return_as_object);
  }

}
