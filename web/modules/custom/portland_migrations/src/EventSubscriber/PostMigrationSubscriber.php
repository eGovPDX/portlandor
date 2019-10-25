<?php

namespace Drupal\portland_migrations\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PostMigrationSubscriber.
 *
 * Perform post-migration cleanup tasks, such as destroying database connections.
 *
 * @package Drupal\portland_migrations
 */
class PostMigrationSubscriber implements EventSubscriberInterface {

  /**
   * Get subscribed events.
   *
   * @inheritdoc
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_IMPORT][] = ['onMigratePostImport'];
    return $events;
  }

  /**
   * Check for our specified last node migration and run our flagging mechanisms.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event object.
   */
  public function onMigratePostImport(MigrateImportEvent $event) {
    if ($event->getMigration()->getBaseId() == 'policies') {
      // if global database connection exists, destroy it
      if (!is_null($_SESSION['policies_dbConn'])) {
        unset($_SESSION['policies_dbConn']);
      }
    }
  }

}