<?php

namespace Drupal\portland\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\samlauth\Event\SamlauthEvents;
use Drupal\samlauth\Event\SamlauthUserSyncEvent;
use Drupal\user\UserInterface;

/**
 * Handle Feeds events
 *
 * @package Drupal\portland\EventSubscriber
 */
class SamlEventsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    return [
      SamlauthEvents::USER_SYNC => 'on_user_sync',
    ];
  }

  /**
   * Update the full name field
   * @param Drupal\feeds\Event\SamlauthUserSyncEvent $event
   */
  public function on_user_sync(SamlauthUserSyncEvent $event) {
    $user = $event->getAccount();
    // Update the user's full name before saving
    $first_name = $user->field_first_name->value;
    $last_name = $user->field_last_name->value;
    if( !empty($first_name) && !empty($last_name)) {
      $user->field_full_name->value = $first_name . ' ' . $last_name;
    }
    else if( !empty($first_name)) {
      $user->field_full_name->value = $first_name;
    }
    else if( !empty($last_name)) {
      $user->field_full_name->value = $last_name;
    }
  }
}
