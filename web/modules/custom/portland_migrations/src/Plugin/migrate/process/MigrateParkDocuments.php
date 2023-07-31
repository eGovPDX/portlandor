<?php

namespace Drupal\portland_migrations\Plugin\migrate\process;

use Drupal\group\Entity\Group;
use Drupal\Core\File\FileSystemInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\media\Entity\Media;
use Drupal\group\Entity\GroupInterface;

/**
 * Download park documents from URLs like:
 * https://www.portlandoregon.gov/parks/finder/index.cfm?action=ViewFile&PolPdfsID=1840
 * Create a media item. Add the media item to the park.
 * 
 * CSV rows: PropertyID,PolPdfsId,Description,AltTagText,DateUpdated,FileName
 * 
 * @MigrateProcessPlugin(
 *   id = "migrate_park_documents"
 * )
 */
class MigrateParkDocuments extends ProcessPluginBase {

  private $fileUrlPrefix = 'https://www.portlandoregon.gov/parks/finder/index.cfm?action=ViewFile&PolPdfsID=';
  
  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Make sure input is valid
    if( empty($value) || !$row->hasSourceProperty('FileName') ) return [];

    // Do nothing if the park is not imported
    $parkNode = $this->getParkNode($row->getSourceProperty('PropertyID'));
    if($parkNode == NULL) return [];
    $result = $parkNode->get('field_documents')->getValue();

    // Build the POG URL of the photo from photo ID. 
    // $value is the PolPhotosId column in CSV
    $pogFileUrl = $this->fileUrlPrefix.$value;
    $pogDescription = $row->getSourceProperty('Description');

    $pogFileName = $row->getSourceProperty('FileName');
    // replace underscores with hypens
    $fileName = preg_replace('/_/', '-', $pogFileName);
    // transliterate filename to remove spaces, punctuation, illegal characters
    if (function_exists("transliterate_filenames_transliteration")) {
      $fileName = transliterate_filenames_transliteration($fileName);
    }
    
    $download_dir_uri = $this->prepareDownloadDirectory();
    $destination_uri = $download_dir_uri . "/" . $fileName;

    // download and save managed file
    try {
      $downloaded_file = system_retrieve_file($pogFileUrl, $destination_uri, TRUE);
    }
    catch (Exception $e) {
      $message = "Error occurred while trying to download URL target at " . $pogFileUrl . " and create managed file. Exception: " . $e->getMessage();
      \Drupal::logger('portland_migrations')->notice($message);
    }

    if( $downloaded_file == FALSE ) {
      echo "Failed to download $pogFileUrl";
      return $result;
    }

    // Create the Media Document item
    $pogAltText = $row->getSourceProperty('AltTagText');
    $media = Media::create([
      'bundle' => 'document',
      'uid' => 1,
      'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
      'name' => $pogFileName,
      'status' => 1,
      'field_document' => [
        'target_id' => $downloaded_file->id()
      ],
      'field_summary' => (empty($pogAltText) ? $pogDescription : $pogAltText) ,
    ]);
    $media->save();
    $media->status->value = 1;
    $media->moderation_state->value = 'published';
    $media->save();

    $this->addEntityToGroup(20, $media); // 20 is the Park bureau group ID

    // Append the new Document to the Park's documents field
    $result[] = [
      'target_id' => $media->id(),
    ];

    return $result;
  }

  protected function addEntityToGroup($group_id, $entity) {
    $group = Group::load($group_id);
    if( $group == NULL ) return;
    $group->addContent($entity, 'group_'.$entity->getEntityTypeId().':'.$entity->bundle());
  }

  protected function prepareDownloadDirectory() {
    // prepare download directory
    $folder_name = date("Y-m") ;
    $folder_uri = \Drupal::service('stream_wrapper_manager')->normalizeUri(\Drupal::config('system.file')->get('default_scheme') . ('://' . $folder_name));
    $public_path = \Drupal::service('file_system')->realpath(\Drupal::config('system.file')->get('default_scheme') . "://");
    $download_path = $public_path . "/" . $folder_name;
    $dir = \Drupal::service('file_system')->prepareDirectory($download_path, FileSystemInterface::CREATE_DIRECTORY);
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