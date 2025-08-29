<?php

namespace Drupal\portland\Drush\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Drupal\group\Entity\GroupRelationship;

class PortlandCommands extends DrushCommands {
  /**
   * Drush command to reset the user sync process so the next cron run will restart the sync process.
   * @command portland:reset_user_sync
   * @aliases pgov-reset-user-sync
   * @description Reset the user sync process so the next cron run will restart the sync process
   */
  public function reset_user_sync() {
    // Clear all items in the queue
    /** @var QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    /** @var QueueInterface $queue */
    $queue = $queue_factory->get('user_sync');
    if ($queue != null) $queue->deleteQueue();

    // Set the flag to start user sync in the next cron run
    \Drupal::state()->set('pgov.user_sync.sync_now', "true");

    // Delete variables tracking user sync progress
    \Drupal::state()->deleteMultiple([
      'pgov.user_sync.stop',
      'pgov.user_sync.last_sync_date.portlandoregon.gov',
      'pgov.user_sync.last_check_removals_date.portlandoregon.gov',
      'pgov.user_sync.drupal_user_offset',
      'pgov.user_sync.resume_url.portlandoregon.gov',
    ]);

    echo "The user sync process will start in the next cron run." . PHP_EOL;
  }


  /**
   * Drush command to delete orphaned nodes belonging to deleted groups.
   * @command portland:delete_orphaned_content
   * @aliases pgov-delete-orphaned-content
   * @description Deletes content still assigned to a group that no longer exists.
   */
  public function delete_orphaned_content() {
    // Process nodes in batches to avoid out of memory errors.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $memory_cache = \Drupal::service('entity.memory_cache');
    $batch_size = 1000;
    $last_nid = 0;
    $processed = 0;

    do {
      $query = $node_storage->getQuery()
        ->accessCheck(FALSE)
        ->sort('nid', 'ASC')
        ->condition('nid', $last_nid, '>')
        ->range(0, $batch_size);

      $nids = $query->execute();

      if (empty($nids)) {
        break;
      }

      echo "Processing node batch " . $last_nid . "-" . $last_nid + $batch_size . PHP_EOL;
      $nodes = $node_storage->loadMultiple($nids);
      foreach ($nodes as $node) {
        $group_contents = GroupRelationship::loadByEntity($node);
        foreach ($group_contents as $group_content) {
          // If the group no longer exists, remove the group content.
          if ($group_content->getGroup() === NULL) {
            $nodeUrl = $node->toUrl('canonical', ['absolute' => TRUE])->toString();
            echo "Deleting group content: $nodeUrl" . PHP_EOL;
            $group_content->delete();

            // If there was only one group content, delete the orphaned node.
            if (count($group_contents) === 1) {
              echo "Deleting orphaned node: $nodeUrl" . PHP_EOL;
              $node->delete();
            }
          }
        }
        $last_nid = $node->id();
        $processed++;
      }

      // Free up memory.
      unset($nodes);
      unset($query);
      unset($nids);
      gc_collect_cycles();
      $memory_cache->deleteAll();
    } while (TRUE);

    echo "Done deleting orphaned group content. Processed $processed nodes." . PHP_EOL;
  }
}
