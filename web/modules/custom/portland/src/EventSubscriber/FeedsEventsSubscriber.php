<?php

namespace Drupal\portland\EventSubscriber;

use Drupal\feeds\Event\EntityEvent;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\group\Entity\Group;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handle Feeds events
 *
 * @package Drupal\portland\EventSubscriber
 */
class FeedsEventsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    return [
      'feeds.process_entity_presave' => 'presave',
      'feeds.process_entity_postsave' => 'postsave',
    ];
  }

  /**
   * Acts on presaving an entity.
   * @param Drupal\feeds\Event\EntityEvent $event
   *
   * @return Drupal\feeds\Event\EntityEvent $event
   */
  public function presave(EntityEvent $event) {
    $node = $event->getEntity();
    $item = $event->getItem();
    $feed = $event->getFeed();

    if ($node->isNew()) {
      if( $node->hasField('field_display_groups') && $feed->hasField('field_parent_group') && 
        count($feed->field_parent_group) > 0 ) {
        $node->field_display_groups->target_id = $feed->field_parent_group[0]->target_id;
      }
      if ($node->hasField('moderation_state') && $feed->hasField('field_publish_new_item')) {
        if ($feed->field_publish_new_item->value) {
          $node->moderation_state->value = 'published';
          $node->status->value = 1;
        }
      }
    }

    $enclosures = $item->get('enclosures');
    if( $enclosures && count($enclosures) > 0 ) {
      $images_html = '';
      foreach($enclosures as $enclosure) {
        // Build unique file name ($fileName) from URL ($enclosure)
        // URL = "https://www.flashalertnewswire.net/images/news/2020-05/3056/134593/banks.jpg"
        // file name = "134593-banks.jpg"
        $parts = explode('/', $enclosure);
        if(count($parts) < 2) continue;
        $fileName = implode('-', array_slice($parts, count($parts)-2 ));
        $download_dir_uri = $this->prepareDownloadDirectory();
        $destination_uri = $download_dir_uri . "/" . $fileName;

        // download and save managed file
        try {
          $downloaded_file = system_retrieve_file($enclosure, $destination_uri, TRUE);
        }
        catch (Exception $e) {
          $message = "Error occurred while trying to download URL target at " . $pogFileUrl . " and create managed file. Exception: " . $e->getMessage();
          \Drupal::logger('portland_migrations')->notice($message);
        }

        if( $downloaded_file == FALSE ) {
          echo "Failed to download $enclosure";
          continue;
        }

        // Create the Media Document item
        $media = Media::create([
          'bundle' => 'image',
          'uid' => 1,
          'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
          'name' => $fileName,
          'status' => 1,
          'image' => [
            'target_id' => $downloaded_file->id(),
            'alt' => $fileName,
          ],
        ]);
        $media->save();
        $media->status->value = 1;
        $media->moderation_state->value = 'published';
        $media->save();

        if( $feed->hasField('field_parent_group') && count($feed->field_parent_group) > 0 ) {
          $this->add_entity_to_group($media, $feed->field_parent_group[0]->target_id);
        }

        // Build HTML
        $media_uuid = $media->uuid();
        $images_html .= "<drupal-entity data-align=\"responsive-right\" data-embed-button=\"image_browser\" data-entity-embed-display=\"media_image\" data-entity-type=\"media\" data-entity-uuid=\"$media_uuid\" data-langcode=\"en\"></drupal-entity>";
      }

      $node->field_body_content->value = $images_html . $node->field_body_content->value;
    }
  }

  /**
   * Acts on postsaving an entity.
   */
  public function postsave(EntityEvent $event) {
    $entity = $event->getEntity();
    $nid = $entity->Id();
    $node = node_load($nid);
    $feed = $event->getFeed();

    if( $feed->hasField('field_parent_group') && count($feed->field_parent_group) > 0 ) {
      $this->add_entity_to_group($node, $feed->field_parent_group[0]->target_id);
    }
  }

  /**
   * Helper funciton to add a node to a group by Group ID
   */
  protected function add_entity_to_group($entity, $gid) {
    if($entity == null) return;

    $pluginId = 'group_'.$entity->getEntityTypeId().':'.$entity->bundle();
    $group = Group::load($gid);
    if( $group == null ) return;
    $relation = $group->getContentByEntityId($pluginId, $entity->id());
    if (!$relation) {
      $group->addContent($entity, 'group_'.$entity->getEntityTypeId().':'.$entity->bundle());
    }
  }

  /**
   * Helper funciton to retrieve images and embed into the body text
   */
  protected function add_images( $description ) {
    $url = $link->getAttribute('src');
    if (is_null($url)) return;

    // Use alt text as the media name if available
    $media_name = $link->getAttribute('alt') ? $link->getAttribute('alt') : $filename;
    $media_type = $this->getMediaType($filename);
    // Create the Media Document item

    if( $media_type == 'image' ) {
      $media = Media::create([
        'bundle' => 'image',
        'uid' => 1,
        'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
        'name' => $media_name,
        'field_title' => $media_name,
        'status' => 1,
        'image' => [
          'target_id' => $downloaded_file->id()
        ],
        'field_summary' => $media_name,
        'field_media_in_library' => 1,
      ]);
    }
    else { // Document
      $media = Media::create([
        'bundle' => 'document',
        'uid' => 1,
        'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
        'name' => $media_name,
        'status' => 1,
        'field_document' => [
          'target_id' => $downloaded_file->id()
        ],
        'field_summary' => $media_name,
      ]);
    }
    $media->save();
    $media->status->value = 1;
    $media->moderation_state->value = 'published';
    $media->save();

    // Replace the old link with a embedded image
    $media_uuid = $media->uuid();
    $newNode = $dom->createDocumentFragment();
    if( $media_type == 'image' ) {
      $newNode->appendXML("<drupal-entity data-align=\"responsive-full\" data-embed-button=\"image_browser\" data-entity-embed-display=\"media_image\" data-entity-type=\"media\" data-entity-uuid=\"$media_uuid\" data-langcode=\"en\"></drupal-entity>");
    }
    else {
      $newNode->appendXML("<drupal-entity data-embed-button=\"document_browser\" data-entity-embed-display=\"view_mode:media.embedded\" data-entity-type=\"media\" data-entity-uuid=\"$media_uuid\" data-langcode=\"en\"></drupal-entity>");
    }
    $link->parentNode->replaceChild($newNode, $link);
  }

  /**
   * Helper funciton to create the download directory for image downloads
   */
  protected function prepareDownloadDirectory() {
    // prepare download directory
    $folder_name = date("Y-m") ;
    $folder_uri = file_build_uri($folder_name);
    $public_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $download_path = $public_path . "/" . $folder_name;
    $dir = file_prepare_directory($download_path, FILE_CREATE_DIRECTORY);
    return $folder_uri;
  }

}