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
   * Drush command to print group user roles.
   */
  #[CLI\Command(name: 'portland:get_group_content', aliases: ['portland-get-group-content'])]
  #[CLI\Usage(name: 'portland:get_group_content GROUP_ID', description: 'GROUP_ID is the group entity ID.')]
  public function get_group_content($group_id = null)
  {
    $group = \Drupal\group\Entity\Group::load((int)$group_id);
    $group_contents = $group->getRelationships();
    foreach ($group_contents as $group_content) {
      $plugin = $group_content->getPlugin();
      if ($plugin->getPluginDefinition()->getEntityTypeId() == "user") {
        $group_entity = $group_content->getEntity();
        print($group_entity->get('name')->value . PHP_EOL);
        print_r($group_content->group_roles->getValue());
      }
    }
  }


  /**
   * Drush command to reset the user sync process so the next cron run will restart the sync process.
   */
  #[CLI\Command(name: 'portland:reset_user_sync')]
  #[CLI\Usage(name: 'portland:reset_user_sync', description: 'Reset the user sync process')]
  public function reset_user_sync()
  {
    // Clear all items in the queue
    /** @var QueueFactory $queue_factory */
    $queue_factory = \Drupal::service('queue');
    /** @var QueueInterface $queue */
    $queue = $queue_factory->get('user_sync');
    if( $queue != null ) $queue->deleteQueue();

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
