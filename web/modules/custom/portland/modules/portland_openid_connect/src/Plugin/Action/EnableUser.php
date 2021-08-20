<?php

namespace Drupal\portland_openid_connect\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Mark user as active.
 *
 * @Action(
 *   id = "enable_user",
 *   label = @Translation("Enable selected users"),
 *   type = "user"
 * )
 */
class EnableUser extends ActionBase
{
  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL)
  {
    if (empty($account)) return;

    // Find the user with email
    $users = \Drupal::entityTypeManager()->getStorage('user')
      ->loadByProperties(['mail' => $account->getEmail()]);

    if (count($users) != 0) {
      $user = array_values($users)[0]; // Assume the lookup returns only one unique user.
      if( ! $user->status->value ) {
        $user->status = 1;
        $user->save();
        \Drupal::logger('portland OpenID')->notice('User enabled: ' . $user->mail->value);
        return $this->t('Bulk operation: user enabled');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE)
  {
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->status->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }
}
