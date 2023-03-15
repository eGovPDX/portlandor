<?php

namespace Drupal\portland\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\user\Entity\User;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

/**
 * Custom drush command file.
 *
 * @package Drupal\portland\Commands
 */
class BatchCommands extends DrushCommands
{
  /**
   * Entity type service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;
  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  private $loggerChannelFactory;


  /**
   * Constructs a new command object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerChannelFactory)
  {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerChannelFactory = $loggerChannelFactory;
  }


  /**
   * Drush command to set user's administration pages language setting to English.
   *
   * @command portland:set_admin_language
   * @aliases portland-set-admin-language
   * @param string $uid
   *   The user ID to set. If omitted all the language of all users will be set.
   * @usage portland:set_admin_language [UID]
   */
  public function set_admin_langguage($uid = 0)
  {
    $user_storage = $this->entityTypeManager->getStorage('user');
    
    if ($uid === 0) {
      $users = $user_storage->getQuery()->condition('preferred_admin_langcode', null, 'is')->execute();
    } else {
      $users = [ $uid ];
    }
    
    foreach ($users as $uid) {
      $user = $user_storage->load($uid);
      $user_email = $user->get('mail')->__get('value');
      $user_pal = $user->get('preferred_admin_langcode')->__get('value');
      
      if ($user_pal === null) {
        $user = $user->set('preferred_admin_langcode', 'en');
        $user->save();
        $user_pal = $user->get('preferred_admin_langcode')->__get('value');
      }
      echo "Admin language for $user_email set to $user_pal\n";
    }
  }

  /**
   * Drush command to copy chart into iframe.
   *
   * @command portland:copy_chart_to_iframe
   * @aliases portland-copy-chart-to-iframe
   * @usage portland:copy_chart_to_iframe
   */
  public function copy_chart_to_iframe()
  {
    echo 'Migrating Chart to IFrame, please save the output to a CSV file to validate the results' . PHP_EOL;
    echo 'chart,iframe,page_using_media' . PHP_EOL;
    $base_url = \Drupal::request()->getSchemeAndHttpHost();

    // Load the entity type manager service.
    $entityTypeManager = \Drupal::entityTypeManager();

    // Load all nodes of the 'article' content type.
    $charts = $entityTypeManager->getStorage('media')->loadByProperties(['bundle' => 'chart']);


    // Loop through the nodes and do something with each one.
    // $count = 3; // For test only
    foreach ($charts as $chart) {
      $chart_redirects = $chart->field_redirects->getValue();
      if(!empty($chart_redirects)) {
        $chart->field_redirects = [];
        $chart->save();
      }

      $new_iframe = Media::create([
        'name' => $chart->name->value,
        'status' => $chart->status->value,
        'bundle' => 'iframe_embed',
        'langcode' => $chart->langcode->value,
        'uid' => $chart->uid->target_id,
        'created' => $chart->created->value,
        'changed' => $chart->changed->value,
        'moderation_state' => $chart->moderation_state->value,
        'thumbnail' => $chart->thumbnail->target_id,
        'field_display_groups' => $chart->field_display_groups->getValue(), // Copy the item list
        'field_media_in_library' => $chart->field_media_in_library->value,
        'field_media_media_remote' => $chart->field_chart_embed->value,
        'field_summary' => $chart->field_summary->value,
        'image' => $chart->image->getValue(),
        'field_redirects' => $chart_redirects ?? [],
      ]);
      $new_iframe->save();

      // Update media UUID in Body
      $nodes_using_chart = array_keys(\Drupal::service('entity_usage.usage')->listUsage($chart)['node'] ?? []);
      $node_urls = [];
      foreach($nodes_using_chart as $node_using_chart) {
        $node = \Drupal\node\Entity\Node::load($node_using_chart);
        $orig_uuid = $chart->uuid->value;
        $orig_text = "<drupal-entity data-align=\"responsive-full\" data-embed-button=\"chart_browser\" data-entity-embed-display=\"view_mode:media.embedded\" data-entity-type=\"media\" data-entity-uuid=\"$orig_uuid\" data-langcode=\"en\"></drupal-entity>";

        $new_uuid = $new_iframe->uuid->value;
        $new_text = "<drupal-entity data-aspect-ratio=\"16/9\" data-embed-button=\"insert_iframe\" data-entity-embed-display=\"view_mode:media.embedded\" data-entity-type=\"media\" data-entity-uuid=\"$new_uuid\" data-langcode=\"en\"></drupal-entity>";

        // Only update the body text if the original text can be found
        if( str_contains($node->field_body_content->value, $orig_text) ) {
          $node->field_body_content->value = str_replace($orig_text, $new_text, $node->field_body_content->value);
          $node->save();
          $node_urls []= $base_url . '/node/' . $node->nid->value;
        }
      }

      echo $base_url . '/media/'. $chart->mid->value . ',' . $base_url . '/media/'. $new_iframe->mid->value . ',' . implode(',', $node_urls) . PHP_EOL;

      // $count--;
      // if($count === 0) break;
    }
  }

  /**
   * Drush command to copy remote map into iframe.
   *
   * @command portland:copy_map_to_iframe
   * @aliases portland-copy-map-to-iframe
   * @usage portland:copy_map_to_iframe
   */
  public function copy_map_to_iframe()
  {
    echo 'Migrating Map to IFrame, please save the output to a CSV file to validate the results' . PHP_EOL;
    echo 'map,iframe,page_using_media' . PHP_EOL;
    $base_url = \Drupal::request()->getSchemeAndHttpHost();

    // Load the entity type manager service.
    $entityTypeManager = \Drupal::entityTypeManager();

    // Load all nodes of the 'article' content type.
    $maps = $entityTypeManager->getStorage('media')->loadByProperties(['bundle' => 'map', 'field_map_type' => 'embedded']);

    // Loop through the nodes and do something with each one.
    // $count = 1; // For test only
    foreach ($maps as $map) {
      if( empty($map->field_map_embed->value) ) {
        echo $base_url . '/media/'. $map->mid->value . ',"No link found. Review manually",' . PHP_EOL;
        continue;
      }

      $map_redirects = $map->field_redirects->getValue();
      // Only clear the field if it's NOT empty
      if(!empty($map_redirects)) {
        $map->field_redirects = [];
        $map->save(); // TODO uncomment
      }

      $new_iframe = Media::create([
        'name' => $map->name->value,
        'status' => $map->status->value,
        'bundle' => 'iframe_embed',
        'langcode' => $map->langcode->value,
        'uid' => $map->uid->target_id,
        'created' => $map->created->value,
        'changed' => $map->changed->value,
        'moderation_state' => $map->moderation_state->value,
        'thumbnail' => $map->thumbnail->target_id,
        'field_display_groups' => $map->field_display_groups->getValue(), // Copy the item list
        'field_media_in_library' => $map->field_media_in_library->value,
        'field_media_media_remote' => $this->getMapUrl($map->field_map_embed->value),
        'field_summary' => $map->field_summary->value,
        'image' => $map->image->getValue(),
        'field_redirects' => $map_redirects ?? [],
      ]);
      $new_iframe->save();

      // Update media UUID in Body
      $nodes_using_map = array_keys(\Drupal::service('entity_usage.usage')->listUsage($map)['node'] ?? []);
      $node_urls = [];
      foreach($nodes_using_map as $node_using_map) {
        $node = \Drupal\node\Entity\Node::load($node_using_map);
        $orig_uuid = $map->uuid->value;
        $orig_text = "<drupal-entity data-align=\"responsive-full\" data-embed-button=\"map_browser\" data-entity-embed-display=\"view_mode:media.embedded\" data-entity-type=\"media\" data-entity-uuid=\"$orig_uuid\" data-langcode=\"en\"></drupal-entity>";

        $new_uuid = $new_iframe->uuid->value;
        $new_text = "<drupal-entity data-aspect-ratio=\"16/9\" data-embed-button=\"insert_iframe\" data-entity-embed-display=\"view_mode:media.embedded\" data-entity-type=\"media\" data-entity-uuid=\"$new_uuid\" data-langcode=\"en\"></drupal-entity>";

        // Only update the body text if the original text can be found
        if( str_contains($node->field_body_content->value, $orig_text) ) {
          $node->field_body_content->value = str_replace($orig_text, $new_text, $node->field_body_content->value);
          $node->save();
          $node_urls []= $base_url . '/node/' . $node->nid->value;
        }
      }

      echo $base_url . '/media/'. $map->mid->value . ',' . $base_url . '/media/'. $new_iframe->mid->value . ',' . implode(',', $node_urls) . PHP_EOL;
    }
  }

  private function getMapUrl($input) {

    if(str_starts_with($input, 'http')) return $input;

    // Extract map URL from iframe embed code for ArcGIS
    $matches = [];
    preg_match('/^(<style>\.embed-container [^>]+<\/style><div class="embed-container">)?<iframe [^>]+ src="(https?:)?(?<url>\/\/[^\/]+\.maps\.arcgis\.com\/apps\/Embed\/index.html\?webmap=[^"]+)".+<\/iframe>(<\/div>)?$/', trim($input), $matches);
    if (isset($matches['url'])) {
      if(str_starts_with($matches['url'], "//"))
        return "https:" . $matches['url'];
      else
        return $matches['url'];
    }
    preg_match('/^<iframe [^>]+ src="(?<url>https:\/\/arcg.is\/[^"]+)".+<\/iframe>$/', trim($input), $matches);
    if (isset($matches['url'])) {
      return $matches['url'];
    }

    // Extract map URL from iframe embed code for Google Maps
    preg_match('/^<iframe src="(?<url>https?:\/\/www\.google\.com\/maps\/embed\?pb=[^"]+)".+<\/iframe>$/', trim($input), $matches);
    if (isset($matches['url'])) {
      return $matches['url'];
    }
    preg_match('/^<iframe src="(?<url>https?:\/\/www\.google\.com\/maps\/d\/embed\?mid=[^"]+)".+<\/iframe>$/', trim($input), $matches);
    if (isset($matches['url'])) {
      return $matches['url'];
    }

    // Portland Maps share URL
    preg_match('/^https?:\/\/www\.portlandmaps\.com\/(detail|apps)\/(?<id>.+)$/', trim($input), $matches);
    if (isset($matches['id'])) {
      return md5($matches['id']);
    }

    // Extract chart URL from iframe embed code for PortlandMaps chart
    preg_match('/^https:\/\/www\.portlandmaps\.com(?<id>[-_\/[:alnum:]]*\/charts\/.*)$/', trim($input), $matches);
    if (isset($matches['id'])) {
      return md5($matches['id']);
    }

    // Provided input was URL so just return input
    return $input;
  }

  /**
   * Drush command to copy printable map into Image (non-PDF) or Document(PDF).
   *
   * @command portland:copy_printable_map
   * @aliases portland-copy-printable-map
   * @usage portland:copy_printable_map
   */
  public function copy_printable_map()
  {
    echo 'Migrating Map image to Image, please save the output to a CSV file to validate the results' . PHP_EOL;
    echo 'map,image_or_document,page_using_media' . PHP_EOL;
    $base_url = \Drupal::request()->getSchemeAndHttpHost();

    // Load the entity type manager service.
    $entityTypeManager = \Drupal::entityTypeManager();

    // Load all nodes of the 'article' content type.
    $maps = $entityTypeManager->getStorage('media')->loadByProperties(['bundle' => 'map', 'field_map_type' => 'print']);

    // Loop through the nodes and do something with each one.
    $new_media = null;
    foreach ($maps as $map) {
      // If there is no map file, copy the preview into a new Image
      if( empty($map->field_map_file[0]) ) {
        $new_media = Media::create([
          'name' => $map->name->value,
          'status' => $map->status->value,
          'bundle' => 'image',
          'langcode' => $map->langcode->value,
          'uid' => $map->uid->target_id,
          'created' => $map->created->value,
          'changed' => $map->changed->value,
          'moderation_state' => $map->moderation_state->value,
          'thumbnail' => $map->thumbnail->target_id,
          'field_display_groups' => $map->field_display_groups->getValue(), // Copy the item list
          'field_media_in_library' => $map->field_media_in_library->value,
          'image' => $map->image->getValue(), // the preview image
        ]);
        $new_media->save();
      }
      else {
        // If there is a map file, copy PDF into Document, and non-PDF into Image
        $mime_type = File::load($map->field_map_file[0]->target_id)->getMimeType();
        $file_size = File::load($map->field_map_file[0]->target_id)->getSize();
        if(str_starts_with($mime_type, "image")) {
          $new_media = Media::create([
            'name' => $map->name->value,
            'status' => $map->status->value,
            'bundle' => 'image',
            'langcode' => $map->langcode->value,
            'uid' => $map->uid->target_id,
            'created' => $map->created->value,
            'changed' => $map->changed->value,
            'moderation_state' => $map->moderation_state->value,
            'thumbnail' => $map->thumbnail->target_id,
            'field_display_groups' => $map->field_display_groups->getValue(), // Copy the item list
            'field_media_in_library' => $map->field_media_in_library->value,
            'image' => [
              'target_id' => $map->field_map_file[0]->target_id,
              // If the alt text for the preview is empty, use the map name
              'alt' => (empty($map->image->alt)) ? $map->name->value : $map->image->alt,
            ],
          ]);
          $new_media->save();
        }
        else {
          $new_media = Media::create([
            'name' => $map->name->value,
            'status' => $map->status->value,
            'bundle' => 'document',
            'langcode' => $map->langcode->value,
            'uid' => $map->uid->target_id,
            'created' => $map->created->value,
            'changed' => $map->changed->value,
            'moderation_state' => $map->moderation_state->value,
            'thumbnail' => $map->thumbnail->target_id,
            'field_display_groups' => $map->field_display_groups->getValue(), // Copy the item list
            'field_media_in_library' => $map->field_media_in_library->value,
            'field_document' => $map->field_map_file[0]->getValue(),
            'field_preview_image' => [
              'target_id' => $map->image->target_id,
              // If the alt text for the preview is empty, use the map name
              'alt' => (empty($map->image->alt)) ? $map->name->value : $map->image->alt,
            ],
            'field_mime_type' => $mime_type,
            'field_file_size' => $file_size,
          ]);
          $new_media->save();
        }
      }

      if(empty($new_media)) continue;
      // Update media UUID in Body
      $nodes_using_map = array_keys(\Drupal::service('entity_usage.usage')->listUsage($map)['node'] ?? []);
      $node_urls = [];
      foreach($nodes_using_map as $node_using_map) {
        $node = \Drupal\node\Entity\Node::load($node_using_map);
        $orig_uuid = $map->uuid->value;
        $orig_text = "<drupal-entity data-align=\"responsive-full\" data-embed-button=\"map_browser\" data-entity-embed-display=\"view_mode:media.embedded\" data-entity-type=\"media\" data-entity-uuid=\"$orig_uuid\" data-langcode=\"en\"></drupal-entity>";

        $new_uuid = $new_media->uuid->value;
        $new_text = "<drupal-entity data-aspect-ratio=\"16/9\" data-embed-button=\"insert_iframe\" data-entity-embed-display=\"view_mode:media.embedded\" data-entity-type=\"media\" data-entity-uuid=\"$new_uuid\" data-langcode=\"en\"></drupal-entity>";

        // Only update the body text if the original text can be found
        if( str_contains($node->field_body_content->value, $orig_text) ) {
          $node->field_body_content->value = str_replace($orig_text, $new_text, $node->field_body_content->value);
          $node->save();
          $node_urls []= $base_url . '/node/' . $node->nid->value;
        }
      }

      echo $base_url . '/media/'. $map->mid->value . ',' . $base_url . '/media/'. $new_media->mid->value . ',' . implode(',', $node_urls) . PHP_EOL;
    }
  }
 
}
