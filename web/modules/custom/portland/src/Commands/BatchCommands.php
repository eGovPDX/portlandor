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
}
