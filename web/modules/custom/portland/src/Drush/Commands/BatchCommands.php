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
use Drupal\group\Entity\GroupInterface;
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
      /** @var User $user */
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

  private $group_field_name_array = [
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
    $groups = Group::loadMultiple();// Load all groups
    foreach ($groups as $group) {
      $group_type_id = $group->getGroupType()->id();
      if($group_type_id == "program") {
        echo "Process program " . $group->label() . PHP_EOL;

        /** @var GroupInterface $group_to_create */
        $group_to_create= \Drupal::entityTypeManager()->getStorage('group')->create(['type' => 'bureau_office']);

        foreach($this->group_field_name_array as $field_name) {
          if($group->hasField($field_name)) {
            $group_to_create->set($field_name, $group->get($field_name)->getValue());
          }
        }
        $group_to_create->set('field_official_organization_name', $group->get('label')->getValue());
        $group_to_create->set('field_group_type', ['target_id' => 844]); // Sub-type is "Program"

        // Change old group's path to PATH-0 to avoid path conflict
        $orig_group_id = $group->id();
        $group->set('field_group_path', [$group->get('field_group_path')->value . '-0']);
        $group->moderation_state->value = "archived";
        $group->status->value = 0;
        $group->revision_log_message->value = "Archived after migrated to Bureau/Offce by the group migration drush command";
        $group->revision_user->target_id = 0;
        $group->changed->value = time();
        $group->save();
        
        $group_to_create->revision_log_message->value = "Created by the group migration drush command. The original group ID is $orig_group_id";
        $group_to_create->save(); // Must save the new group in order to get the ID

        // TODO: Copy revisions?

        // Copy all group content by updating the group ID
        $group_to_create_id = $group_to_create->id();
        $group_contents = $group->getContent();
        foreach($group_contents as $group_content) {
          $group_content->gid->target_id = $group_to_create_id;
          $group_content->save();
        }

        // Update all usage of the original group
        /*
        List of entity fields that reference to group:
          node->field_display_groups
          node->field_body_content
          media->field_display_groups
          feeds_feed->field_parent_group
          group->field_featured_groups
          group->field_parent_group
          node->field_bureau (Council Document only. No need to migrate)
          group->field_body_content (Elected only. Easy to check manually)
        */
        $usage_service = \Drupal::service('entity_usage.usage');
        $usage_list = $usage_service->listSources($group);
        // Two source types: [group, node]
        foreach($usage_list as $source_type => $usage_list_by_type) {
          // $usage is [ entity ID => [ entry for each revision ] ]
          foreach($usage_list_by_type as $entity_id => $usage) {
            if($source_type == 'node') {
              $source_node = \Drupal\node\Entity\Node::load($entity_id);
              $field_name = $usage[0]['field_name']; // The first item is the latest revision

              switch($field_name) {
                case 'field_parent_group':
                  if($source_node->get('field_parent_group')->target_id == $orig_group_id) {
                    $source_node->get('field_parent_group')->target_id = $group_to_create_id;
                    $source_node->revision_log->value = "field_parent_group updated by Drush command";
                    $source_node->revision_uid = 0;
                    $source_node->save();
                  }
                  break;
                case 'field_display_groups':
                  /** @var EntityReferenceFieldItemListInterface $display_groups */
                  $display_groups = $source_node->get('field_display_groups');
                  foreach($display_groups->referencedEntities() as $index => $display_group) {
                    if($source_node->field_display_groups[$index]->target_id == $orig_group_id) {
                      $source_node->field_display_groups[$index]->target_id = $group_to_create_id;
                      $source_node->revision_log->value = "field_display_groups updated by Drush command";
                      $source_node->revision_uid = 0;
                      $source_node->save();
                    }
                  }
                  break;
                case 'field_body_content':
                  // Update field_body_content: do a search and replace for group UUID and the ID in href
                  // <a data-entity-type="group" data-entity-uuid="2697532a-7898-40a5-b86a-b36aae959494" href="/group/40">
                  $orig_group_uuid = $group->uuid();
                  $group_to_create_uuid = $group_to_create->uuid();
                  $text_to_be_replaced = 'data-entity-uuid="' . $orig_group_uuid . '" href="/group/' . $orig_group_id;
                  $replacement_text = 'data-entity-uuid="' . $group_to_create_uuid . '" href="/group/' . $group_to_create_id;
                  $replacement_count = 0;
                  $source_node->field_body_content->value = str_replace($text_to_be_replaced, $replacement_text, $source_node->field_body_content->value, $replacement_count);
                  if($replacement_count > 0) { // Only save if the body content is updated
                    $source_node->revision_log->value = "field_body_content updated by Drush command";
                    $source_node->revision_uid = 0;
                    $source_node->save();
                  }
                  break;
              }
            }
            else if($source_type == 'group') {
              $source_group = \Drupal\group\Entity\Group::load($entity_id);
              $field_name = $usage[0]['field_name']; // The latest revision
              switch($field_name) {
                case 'field_featured_groups':
                  /** @var EntityReferenceFieldItemListInterface $featured_groups */
                  $featured_groups = $source_group->get('field_featured_groups');
                  foreach($featured_groups->referencedEntities() as $index => $featured_group) {
                    if($source_group->field_featured_groups[$index]->target_id == $orig_group_id) {
                      $source_group->field_featured_groups[$index]->target_id = $group_to_create_id;
                      $source_group->revision_log_message->value = "field_featured_groups updated by Drush command";
                      $source_group->revision_user->target_id = 0;
                      $source_group->revision_created->value = time();
                      $source_group->save();
                    }
                  }
                  break;
                case 'field_parent_group':
                  if($source_group->get('field_parent_group')->target_id == $orig_group_id) {
                    $source_group->get('field_parent_group')->target_id = $group_to_create_id;
                    $source_group->revision_log_message->value = "field_parent_group updated by Drush command";
                    $source_group->revision_user->target_id = 0;
                    $source_group->revision_created->value = time();
                    $source_group->save();
                  }
                  break;
              }
            }
          }
        }

        // TEST ONLY: only copy one program
        // break;
      }
    }
  }
}
