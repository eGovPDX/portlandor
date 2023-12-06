<?php

namespace Drupal\portland\Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Utility\Token;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\user\Entity\User;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

/**
 * Custom drush command file.
 *
 * @package Drupal\portland\Commands
 */
final class BatchCommands extends DrushCommands
{

  /**
   * Constructs a TestCommands object.
   */
  public function __construct(
    private readonly Token $token,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerChannelInterface $loggerChannelSystem,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('token'),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.system'),
    );
  }

  /**
   * Drush command to set user's administration pages language setting to English.
   */
  #[CLI\Command(name: 'portland:set_admin_language', aliases: ['portland-set-admin-language'])]
  #[CLI\Argument(name: 'uid', description: 'The user ID.')]
  #[CLI\Usage(name: 'portland:set_admin_language UID', description: 'Command followed by optinal user ID')] 
  public function set_admin_langguage($uid = 0)
  {
    $user_storage = $this->entityTypeManager->getStorage('user');

    if ($uid === 0) {
      $users = $user_storage->getQuery()->condition('preferred_admin_langcode', null, 'is')
        ->accessCheck()
        ->execute();
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
   * Drush command to copy Audio into Video.
   */
   #[CLI\Command(name: 'portland:remove_audio', aliases: ['portland-remove-audio'])]
   #[CLI\Usage(name: 'portland:remove_audio', description: 'Command without parameter')] 
  public function remove_audio()
  {
    // Load all groups
    $groups = Group::loadMultiple();
    foreach ($groups as $group) {
      $group_type_id = $group->getGroupType()->id();
      if($group_type_id == "program") {
        echo "Process program " . $group->label() . PHP_EOL;

        $audio_contents = $group->getContent(null, ["type"=>"program-group_media-audio"]);
        foreach($audio_contents as $audio_content) {
          echo "Delete group audio " . $audio_content->label() . PHP_EOL;
          $audio_content->delete();
        }
      }
      else if($group_type_id == "project") {
        echo "Process project " . $group->label() . PHP_EOL;
        $audio_contents = $group->getContent(null, ["type"=>"project-group_media-audio"]);
        foreach($audio_contents as $audio_content) {
          echo "Delete group audio " . $audio_content->label() . PHP_EOL;
          $audio_content->delete();
        }
      }
    }
  }
  /**
   * Drush command to copy Audio into Video.
   */
   #[CLI\Command(name: 'portland:copy_audio_to_video', aliases: ['portland-copy-audio-to-video'])]
   #[CLI\Usage(name: 'portland:copy_audio_to_video', description: 'Command without parameter')] 
  public function copy_audio_to_video()
  {
    echo 'Migrating Audio to Video, please save the output to a CSV file to validate the results' . PHP_EOL;
    echo 'audio,video,page_using_media' . PHP_EOL;
    $base_url = \Drupal::request()->getSchemeAndHttpHost();

    $entityTypeManager = \Drupal::entityTypeManager();
    $audios = $entityTypeManager->getStorage('media')->loadByProperties(['bundle' => 'audio']);

    foreach ($audios as $audio) {
      $audio_redirects = $audio->field_redirects->getValue();
      if(!empty($audio_redirects)) {
        $audio->field_redirects = [];
        $audio->save();
      }

      $new_video = Media::create([
        'name' => $audio->name->value,
        'status' => $audio->status->value,
        'bundle' => 'video',
        'langcode' => $audio->langcode->value,
        'uid' => $audio->uid->target_id,
        'created' => $audio->created->value,
        'changed' => $audio->changed->value,
        'moderation_state' => $audio->moderation_state->value,
        'thumbnail' => $audio->thumbnail->target_id,
        'field_display_groups' => $audio->field_display_groups->getValue(), // Copy the item list
        'field_media_in_library' => $audio->field_media_in_library->value,
        'field_media_video_embed_field' => $audio->field_media_video_embed_field->getValue(),
        'field_caption' => $audio->field_caption->value,
        'field_license' => $audio->field_license->getValue(),
        'field_creator' => $audio->field_creator->value,
        'field_source' => $audio->field_source->value,
        'field_title' => $audio->field_title->value,
        'field_transcript' => $audio->field_transcript->value,
      ]);
      $new_video->save();

      // Update media UUID in Body
      $nodes_using_audio = array_keys(\Drupal::service('entity_usage.usage')->listSources($audio)['node'] ?? []);
      $node_urls = [];
      foreach($nodes_using_audio as $node_using_audio) {
        $node = Node::load($node_using_audio);
        $orig_uuid = $audio->uuid->value;
        $new_uuid = $new_video->uuid->value;
        $node->field_body_content->value = str_replace($orig_uuid, $new_uuid, $node->field_body_content->value);
        $node->save();
        $node_urls []= $base_url . '/node/' . $node->nid->value;
      }

      echo $base_url . '/media/'. $audio->mid->value . ',' . $base_url . '/media/'. $new_video->mid->value . ',' . implode(',', $node_urls) . PHP_EOL;
    }
  }

  private $GROUP_FIELD_ARRAY = [
    'langcode',
    'label',
    'uid',
    'created',
    'changed',
    'status',

    'moderation_state',

    'field_shortname_or_acronym',
    'field_contact',
    'field_disable_legacy_paths_block',
    'field_enable_advisory_menu_item',
    'field_group_menu_toggle',
    'field_enable_bids_and_proposals',
    'field_enable_blog_menu_item',
    'field_enable_const_project_menu',
    'field_enable_const_proj_complete',
    'field_enable_documents_menu_item',
    'field_enable_events_menu_item',
    'field_enable_news_and_notices_me',
    'field_enable_past_meetings',
    'field_enable_permits',
    'field_enable_press_releases',
    'field_enable_programs_menu_item',
    'field_enable_projects_menu_item',
    'field_enable_public_notices',
    'field_enable_reports',
    'field_enable_services_and_inform',
    'field_featured_content',
    'field_featured_media',
    'field_group_path',
    'field_location',
    'field_logo_svg',
    'field_address',
    'field_menu_link',
    'field_migration_status',
    'field_parent_group',
    'field_featured_groups',
    'field_search_keywords',
    'field_summary',
    'field_topics',
  ];

  /**
   * Drush command to copy Program into Bureau/Office.
   */
   #[CLI\Command(name: 'portland:copy_program_to_bureau', aliases: ['portland-copy-program-to-bureau'])]
   #[CLI\Usage(name: 'portland:copy_program_to_bureau', description: 'Command without parameter')] 
  public function copy_program_to_bureau()
  {
    // Load all groups
    $groups = Group::loadMultiple();
    foreach ($groups as $group) {
      $group_type_id = $group->getGroupType()->id();
      if($group_type_id == "program") {
        echo "Process program " . $group->label() . PHP_EOL;

        $group_to_create= \Drupal::entityTypeManager()->getStorage('group')->create(['type' => 'bureau_office']);

        foreach($this->GROUP_FIELD_ARRAY as $field_name) {
          if($group->hasField($field_name)) {
            $group_to_create->set($field_name, $group->get($field_name)->getValue());
          }
        }
        $group_to_create->set('field_official_organization_name', $group->get('label')->getValue());
        $group_to_create->set('field_group_type', ['target_id' => 844]);

        // TODO: set old group's path to PATH-0 before saving
        $group->set('field_group_path', [$group->get('field_group_path')->value . '-0']);
        // $group->save();
        // $group_to_create->save();


        // Copy members

        // Copy content

        // Copy media

        // TEST ONLY: only copy one program
        break;
      }
    }
  }
}
