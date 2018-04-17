<?php

namespace Drupal\actions_permissions\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager;

/**
 * Defines module event subscriber class.
 *
 * Alters actions to make use of permissions created by the module.
 */
class ActionsPermissionsEventSubscriber implements EventSubscriberInterface {

  // Subscribe to the VBO event with low priority
  // to let other modules alter requirements first.
  const PRIORITY = -999;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ViewsBulkOperationsActionManager::ALTER_ACTIONS_EVENT][] = ['alterActions', static::PRIORITY];
    return $events;
  }

  /**
   * Alter the actions' definitions.
   *
   * @var \Symfony\Component\EventDispatcher\Event $event
   *   The event to respond to.
   */
  public function alterActions(Event $event) {

    // Don't alter definitions if this is invoked by the
    // own permissions creating method.
    if (!empty($event->alterParameters['skip_actions_permissions'])) {
      return;
    }

    foreach ($event->definitions as $action_id => $definition) {

      // Only process actions that don't define their own requirements.
      if (empty($definition['requirements'])) {
        $permission_id = 'execute ' . $definition['id'];
        if (empty($definition['type'])) {
          $permission_id .= ' all';
        }
        else {
          $permission_id .= ' ' . $definition['type'];
        }
        $definition['requirements']['_permission'] = $permission_id;
        $event->definitions[$action_id] = $definition;
      }
    }
  }

}
