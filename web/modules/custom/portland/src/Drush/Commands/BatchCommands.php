<?php

namespace Drupal\portland\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Utility\Token;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\media\Entity\Media;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

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
  public static function create(ContainerInterface $container)
  {
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
      $users = [$uid];
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
      if ($group_type_id == "program") {
        echo "Process program " . $group->label() . PHP_EOL;

        $audio_contents = $group->getContent(null, ["type" => "program-group_media-audio"]);
        foreach ($audio_contents as $audio_content) {
          echo "Delete group audio " . $audio_content->label() . PHP_EOL;
          $audio_content->delete();
        }
      } else if ($group_type_id == "project") {
        echo "Process project " . $group->label() . PHP_EOL;
        $audio_contents = $group->getContent(null, ["type" => "project-group_media-audio"]);
        foreach ($audio_contents as $audio_content) {
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
      if (!empty($audio_redirects)) {
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
      foreach ($nodes_using_audio as $node_using_audio) {
        $node = Node::load($node_using_audio);
        $orig_uuid = $audio->uuid->value;
        $new_uuid = $new_video->uuid->value;
        $node->field_body_content->value = str_replace($orig_uuid, $new_uuid, $node->field_body_content->value);
        $node->save();
        $node_urls[] = $base_url . '/node/' . $node->nid->value;
      }

      echo $base_url . '/media/' . $audio->mid->value . ',' . $base_url . '/media/' . $new_video->mid->value . ',' . implode(',', $node_urls) . PHP_EOL;
    }
  }

  /**
   * Drush command to print group user roles.
   */
  #[CLI\Command(name: 'portland:get_group_content', aliases: ['portland-get-group-content'])]
  #[CLI\Usage(name: 'portland:get_group_content GROUP_ID', description: 'GROUP_ID is the group entity ID.')]
  public function get_group_content($group_id = null)
  {
    $group = \Drupal\group\Entity\Group::load((int)$group_id);
    $group_contents = $group->getContent();
    foreach ($group_contents as $group_content) {
      $plugin = $group_content->getContentPlugin();
      $entityBundle = $plugin->getEntityBundle(); // content or media bundle machine name
      if ($plugin->getEntityTypeId() == "user") {
        $group_entity = $group_content->getEntity();
        print_r($group_content->group_roles->getValue());
      }
    }
  }
}
