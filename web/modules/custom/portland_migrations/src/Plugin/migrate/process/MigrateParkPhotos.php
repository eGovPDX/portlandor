<?php

namespace Drupal\portland_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\media\Entity\Media;

/**
 * Download park images from URLs like:
 * https://www.portlandoregon.gov/parks/finder/index.cfm?action=ViewFile&PolPhotosID=678
 * Create a media item. Add the media item to the park.
 * 
 * CSV rows: PropertyID,PolPhotosId,Description,AltTagText,FileName
 * 
 * @MigrateProcessPlugin(
 *   id = "migrate_park_photos"
 * )
 */
class MigrateParkPhotos extends ProcessPluginBase {

  private $fileUrlPrefix = 'https://www.portlandoregon.gov/parks/finder/index.cfm?action=ViewFile&PolPhotosID=';
  
  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Make sure input is valid
    if( empty($value) || !$row->hasSourceProperty('FileName') ) return [];

    $parkNode = $this->getParkNode($row->getSourceProperty('PropertyID'));
    if($parkNode == NULL) return [];

    // Build the POG URL of the photo from photo ID. 
    // $value is the PolPhotosId column in CSV
    $pogImageUrl = $this->fileUrlPrefix.$value;

    $fileName = $row->getSourceProperty('FileName');
    // replace underscores with hypens
    $fileName = preg_replace('/_/', '-', $fileName);
    // transliterate filename to remove spaces, punctuation, illegal characters
    if (function_exists("transliterate_filenames_transliteration")) {
      $fileName = transliterate_filenames_transliteration($fileName);
    }

    $download_dir_uri = $this->prepareDownloadDirectory();
    $destination_uri = $download_dir_uri . "/" . $fileName;

    // download and save managed file
    try {
      $downloaded_file = system_retrieve_file($pogImageUrl, $destination_uri, TRUE);
    }
    catch (Exception $e) {
      $message = "Error occurred while trying to download URL target at " . $pogImageUrl . " and create managed file. Exception: " . $e->getMessage();
      \Drupal::logger('portland_migrations')->notice($message);
    }

    // Create the Media Image item
    $parkTitle = $parkNode->getTitle();
    $pogAltText = $row->getSourceProperty('AltTagText');
    $imageTitle = ( empty($pogAltText) ) ? $parkTitle : ($parkTitle.' - '.$pogAltText);
    $media = Media::create([
      'bundle' => 'image',
      'uid' => 1,
      'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
      'name' => $imageTitle,
      'status' => 1,
      'image' => [
        'target_id' => $downloaded_file->id()
      ],
    ]);
    $media->save();
    $media->status->value = 1;
    $media->moderation_state->value = 'published';
    $media->save();

    $this->addEntityToGroup(20, $media); // 20 is the Park bureau group ID

    // Append the new Image to the Park's Images field
    $result = $parkNode->get('field_images')->getValue();
    $result[] = [
      'target_id' => $media->id(),
    ];

    return $result;
  }

  protected function addEntityToGroup($group_id, $entity) {
    $group = Drupal\group\Entity\Group::load($group_id);
    if( $group == NULL ) return;
    $group->addContent($entity, 'group_'.$entity->getEntityTypeId().':'.$entity->bundle());
  }

  protected function prepareDownloadDirectory() {
    // prepare download directory
    $folder_name = date("Y-m") ;
    $folder_uri = file_build_uri($folder_name);
    $public_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $download_path = $public_path . "/" . $folder_name;
    $dir = file_prepare_directory($download_path, FILE_CREATE_DIRECTORY);
    return $folder_uri;
  }

  protected function getParkNode($propertyId){
    $nodes = \Drupal::entityTypeManager()
    ->getStorage('node')
    ->loadByProperties(['field_property_id' => $propertyId, 'type' => 'park_facility']);

    if(count($nodes) === 0) return NULL;
    // Only expect one result. The key of the first array element is the park's NID in POWR.
    return array_values($nodes)[0];
  }
}