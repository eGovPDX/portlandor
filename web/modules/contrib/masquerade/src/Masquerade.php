<?php

namespace Drupal\masquerade;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * Defines a masquerade service to switch user account.
 */
class Masquerade {
  use StringTranslationTrait;
  use LinkGeneratorTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The session manager.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * The logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * Constructs Masquerade object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   The session manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger instance.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, SessionManagerInterface $session_manager, LoggerInterface $logger, PermissionHandlerInterface $permission_handler) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->sessionManager = $session_manager;
    $this->logger = $logger;
    $this->permissionHandler = $permission_handler;
  }

  /**
   * Returns whether the current user is masquerading.
   *
   * @return bool
   */
  public function isMasquerading() {
    // @todo Check to use some session related service.
    return !empty($_SESSION['masquerading']);
  }

  /**
   * Masquerades the current user as a given user.
   *
   * @param \Drupal\user\UserInterface $target_account
   *   The user account object to masquerade as.
   *
   * @return bool
   *   TRUE when masqueraded, FALSE otherwise.
   */
  public function switchTo(UserInterface $target_account) {
    $account = $this->currentUser->getAccount();

    // Call logout hooks when switching from original user.
    $this->moduleHandler->invokeAll('user_logout', [$account]);

    // Regenerate the session ID to prevent against session fixation attacks.
    $this->sessionManager->regenerate();

    $_SESSION['masquerading'] = $account->id();

    // Supposed "safe" user switch method:
    // https://www.drupal.org/node/218104
    //$accountSwitcher = Drupal::service('account_switcher');
    //$accountSwitcher->switchTo(new UserSession(array('uid' => $account->id())));
    $this->currentUser->setAccount($target_account);
    \Drupal::service('session')->set('uid', $target_account->id());

    // Call all login hooks when switching to masquerading user.
    $this->moduleHandler->invokeAll('user_login', [$target_account]);

    $this->logger->info('User %username masqueraded as %target_username.', array(
      '%username' => $account->getDisplayName(),
      '%target_username' => $target_account->getDisplayName(),
      'link' => $this->l($this->t('view'), $target_account->toUrl()),
    ));
    return TRUE;
  }

  /**
   * Switching back to previous user.
   *
   * @return bool
   *   TRUE when switched back, FALSE otherwise.
   */
  public function switchBack() {
    if (empty($_SESSION['masquerading'])) {
      return FALSE;
    }
    $new_user = $this->entityTypeManager
      ->getStorage('user')
      ->load($_SESSION['masquerading']);

    // Ensure the flag is cleared.
    unset($_SESSION['masquerading']);
    if (!$new_user) {
      return FALSE;
    }

    $account = $this->currentUser->getAccount();
    // Call logout hooks when switching from masquerading user.
    $this->moduleHandler->invokeAll('user_logout', [$account]);
    // Regenerate the session ID to prevent against session fixation attacks.
    // @todo Maybe session service migrate.
    $this->sessionManager->regenerate();

    $this->currentUser->setAccount($new_user);
    \Drupal::service('session')->set('uid', $new_user->id());

    // Call all login hooks when switching back to original user.
    $this->moduleHandler->invokeAll('user_login', [$new_user]);

    $this->logger->info('User %username stopped masquerading as %old_username.', array(
      '%username' => $new_user->getDisplayName(),
      '%old_username' => $account->getDisplayName(),
      'link' => $this->l($this->t('view'), $new_user->toUrl()),
    ));
    return TRUE;
  }

  /**
   * Returns module provided permissions.
   *
   * @return array
   *   Array of permission names.
   */
  public function getPermissions() {
    $permissions = [];
    foreach ($this->permissionHandler->getPermissions() as $name => $permission) {
      if ($permission['provider'] === 'masquerade') {
        // Filter only module's permissions.
        $permissions[] = $name;
      }
    }
    return $permissions;
  }

}
