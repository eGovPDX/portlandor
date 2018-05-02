<?php

namespace Drupal\masquerade\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\masquerade\Masquerade;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for switch and back to masquerade as user.
 */
class SwitchController extends ControllerBase {

  /**
   * The masquerade service.
   *
   * @var \Drupal\masquerade\Masquerade
   */
  protected $masquerade;

  /**
   * Constructs a new SwitchController object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user
   * @param \Drupal\masquerade\Masquerade $masquerade
   *   The masquerade service.
   */
  public function __construct(AccountInterface $current_user, Masquerade $masquerade) {
    $this->currentUser = $current_user;
    $this->masquerade = $masquerade;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('masquerade')
    );
  }

  /**
   * Masquerades the current user as a given user.
   *
   * Access to masquerade as the target user account has to checked by all callers
   * via masquerade_target_user_access() already.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account object to masquerade as.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to previous page.
   *
   * @see this::getRedirectResponse()
   */
  public function switchTo(UserInterface $user) {
    // Store current user for messages.
    $account = $this->currentUser;
    $error = masquerade_switch_user_validate($user);
    if (empty($error)) {
      if ($this->masquerade->switchTo($user)) {
        drupal_set_message($this->t('You are now masquerading as @user.', array(
          '@user' => $account->getDisplayName(),
        )));
      }
    }
    else {
      drupal_set_message($error, 'error');
    }
    return $this->getRedirectResponse();
  }

  /**
   * Allows a user who is currently masquerading to become a new user.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response to previous page.
   *
   * @see this::getRedirectResponse()
   */
  public function switchBack(Request $request) {
    // Store current user name for messages.
    $account_name = $this->currentUser->getDisplayName();
    if ($this->masquerade->switchBack()) {
      drupal_set_message($this->t('You are no longer masquerading as @user.', array(
        '@user' => $account_name,
      )));
    }
    else {
      drupal_set_message($this->t('Error trying unmasquerading as @user.', array(
        '@user' => $account_name,
      )), 'error');
    }
    return $this->getRedirectResponse($request);
  }

  /**
   * Returns redirect response to previous page.
   *
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   (Optional) The request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect.
   *
   * @see \Drupal\Core\EventSubscriber\RedirectResponseSubscriber::checkRedirectUrl()
   */
  protected function getRedirectResponse($request = NULL) {
    if (!isset($request)) {
      $request = \Drupal::request();
    }
    $destination = \Drupal::destination();
    if ($destination_path = $destination->get()) {
      // Try destination first.
      $url = Url::createFromRequest(Request::create($destination_path));
    }
    elseif ($redirect_path = $request->server->get('HTTP_REFERER')) {
      // Parse referer to get route name if any.
      $url = Url::createFromRequest(Request::create($redirect_path));
    }
    else {
      // Fallback to front page if no referrer.
      $url = Url::fromRoute('<front>');
    }
    // Check access for redirected url.
    if (!$url->access($this->currentUser)) {
      // Fallback to front page redirect.
      $url = Url::fromRoute('<front>');
    }
    $url = $url->setAbsolute()->toString();
    if ($destination_path) {
      // Override destination because it will take over response.
      $request->query->set('destination', $url);
      $destination->set($url);
    }
    return new RedirectResponse($url);
  }

}
