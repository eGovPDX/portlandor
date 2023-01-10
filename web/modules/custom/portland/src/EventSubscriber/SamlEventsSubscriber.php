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
    $attributes = $event->getAttributes();
    // If the attribute names are missing, skip this step
    if( !isset($attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname']) ||
      !isset($attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname']) ) {
      return;
    }

    // Update the user's full name before saving
    $user = $event->getAccount();
    $first_name = $attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname'][0];
    $last_name = $attributes['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname'][0];
    if( !empty($first_name) && !empty($last_name)) {
      $user->field_full_name->value = $first_name . ' ' . $last_name;
    }
    elseif( !empty($first_name)) {
      $user->field_full_name->value = $first_name;
    }
    elseif( !empty($last_name)) {
      $user->field_full_name->value = $last_name;
    }
  }
}
