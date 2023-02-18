<?php

namespace Drupal\portland\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\user\Entity\User;
use Drupal\media\Entity\Media;

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
   * Drush command to set user's administration pages language setting to English.
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
      $chart->field_redirects = [];
      $chart->save();

      $new_iframe = Media::create([
        'name' => $chart->name->value,
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
        'field_redirects' => $chart_redirects,
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
}
