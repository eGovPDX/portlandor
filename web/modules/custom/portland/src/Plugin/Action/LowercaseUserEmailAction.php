<?php

namespace Drupal\portland\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\UserInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;

/**
 * Converts a user account's email address to all lower case.
 */
#[Action(
  id: 'portland_lowercase_user_email',
  label: new TranslatableMarkup('Update email address to lower case'),
  type: 'user',
)]
class LowercaseUserEmailAction extends ViewsBulkOperationsActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute(?UserInterface $user = NULL) {
    if (empty($user)) {
      return $this->t('No user specified.');
    }

    $email = $user->getEmail();
    $lowercase_email = mb_strtolower($email);
    if ($email === $lowercase_email) {
      return $this->t('Email address is already lower case.');
    }

    $user->setEmail($lowercase_email);
    $user->save();

    return $this->t('Updated email address to lower case.');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::forbidden();
    if ($object instanceof UserInterface) {
      /** @var \Drupal\user\UserInterface $object */
      // Don't allow non-admins to edit admin users.
      $result = ($object->hasRole('administrator') && !$account->hasRole('administrator')) ? AccessResult::forbidden() : AccessResult::allowed();
    }

    return $return_as_object ? $result : $result->isAllowed();
  }
}
