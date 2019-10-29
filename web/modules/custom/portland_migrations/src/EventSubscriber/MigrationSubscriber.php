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
class MigrationSubscriber implements EventSubscriberInterface {

  /**
   * Get subscribed events.
   *
   * @inheritdoc
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_IMPORT][] = ['onMigratePostImport'];
    $events[MigrateEvents::PRE_IMPORT][] = ['onMigratePreImport'];
    return $events;
  }

  /**
   * For Policies migration, prepare download directory before migration runs
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event object.
   */
  public function onMigratePreImport(MigrateImportEvent $event) {
    if ($event->getMigration()->getBaseId() == 'policies') {
      // prepare policies media download directory
      $folder_name = date("Y-m") ;
      $folder_uri = file_build_uri($folder_name);
      $public_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
      $download_path = $public_path . "/" . $folder_name;
      $dir = file_prepare_directory($download_path, FILE_CREATE_DIRECTORY);
    }
  }

  /**
   * For Policies import, unset session-level database connection
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