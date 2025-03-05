<?php

namespace Drupal\portland\Drush\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Drupal\focal_point\Plugin\ImageEffect\FocalPointScaleAndCropImageEffect;

class PortlandCommands extends DrushCommands {
  /**
   * Drush command to reset the user sync process so the next cron run will restart the sync process.
   */
  #[CLI\Command(name: 'portland:reset_user_sync')]
  #[CLI\Usage(name: 'portland:reset_user_sync', description: 'Reset the user sync process')]
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
}
