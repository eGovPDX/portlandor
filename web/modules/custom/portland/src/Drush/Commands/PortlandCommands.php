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

  /**
   * Drush command to get a list of all media IDs using focal_point.
   */
  #[CLI\Command(name: 'portland:get_mids_using_focal_point')]
  #[CLI\Usage(name: 'portland:get_mids_using_focal_point', description: 'Get a list of all media IDs using focal_point.')]
  public function get_mids_using_focal_point() {
    $entity_usage = \Drupal::service('entity_usage.usage');
    /** @var \Drupal\focal_point\FocalPointManagerInterface $focal_point_manager */
    $focal_point_manager = \Drupal::service('focal_point.manager');
    $entity_type_manager = \Drupal::entityTypeManager();
    $media_storage = $entity_type_manager->getStorage('media');
    $images = $media_storage->getQuery()
      ->accessCheck(false)
      ->condition('bundle', 'image')
      ->execute();

    foreach ($images as $mid) {
      $media = $media_storage->load($mid);
      $usage_data = $entity_usage->listSources($media);
      if (empty($usage_data)) {
        continue;
      }

      $crop = $focal_point_manager->getCropEntity($media->image->entity, 'focal_point');
      $crop_values = $focal_point_manager->absoluteToRelative($crop->x->value, $crop->y->value, $media->image->width, $media->image->height);
      if ($crop_values['x'] !== 50 || $crop_values['y'] !== 50) {
        print($mid . "\n");
      }
    }
  }

   /**
   * Drush command to migrate focal_point crops to image_widget_crop. Requires stdin input of media IDs separated by newline.
   */
  #[CLI\Command(name: 'portland:migrate_focal_point')]
  #[CLI\Usage(name: 'portland:migrate_focal_point', description: 'Migrate focal_point crops to image_widget_crop. Requires stdin input of media IDs separated by newline.')]
  public function migrate_focal_point() {
    /** @var \Drupal\focal_point\FocalPointManagerInterface $focal_point_manager */
    $focal_point_manager = \Drupal::service('focal_point.manager');
    $entity_type_manager = \Drupal::entityTypeManager();
    $media_storage = $entity_type_manager->getStorage('media');
    $mids = explode("\n", file_get_contents("php://stdin"));
    /** @var \Drupal\Core\Image\ImageFactory $image */
    $image_factory = \Drupal::service('image.factory');
    /** @var \Drupal\focal_point\Plugin\ImageEffect\FocalPointScaleAndCropImageEffect $image_effect */
    $image_effect = FocalPointScaleAndCropImageEffect::create(\Drupal::getContainer(), [], 'plugin_id', []);

    foreach ($mids as $mid) {
      if (empty($mid)) return;

      $media = $media_storage->load((int) $mid);

      // $crop = $focal_point_manager->getCropEntity($media->image->entity, 'focal_point');
      // $crop->setSize($media->image->width, $media->image->height);
      // $crop_position = $crop->position();

      // $this->cropStorage->create([
      //   'type' => $crop_type,
      //   'x' => (int) round($this->originalImageSize['width'] / 2),
      //   'y' => (int) round($this->originalImageSize['height'] / 2),
      //   'width' => $this->configuration['width'],
      //   'height' => $this->configuration['height'],
      // ]);
    }
  }
}
