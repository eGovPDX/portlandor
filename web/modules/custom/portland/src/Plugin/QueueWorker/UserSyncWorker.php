<?php

namespace Drupal\portland\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\portland_openid_connect\Util\PortlandOpenIdConnectUtil;
use Exception;

/**
 * SyncUsersWorker class.
 *
 * A worker plugin to consume items from "user_sync"
 * and synchronize users from Entra ID.
 *
 * @QueueWorker(
 *   id = "user_sync",
 *   title = @Translation("Synchronize Users Queue"),
 *   cron = {"time" = 120}
 * )
 */
class UserSyncWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface
{
  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param array $data
   * [
   *   "access_token" => $access_token,
   *   "domain" => $domain,
   *   "users" => users[{...}]
   * ]
   * 
   * User data example:
   * {
   *     "id": "1234dfc6-310c-4d5e-aa62-6237a8222f1b",
   *     "accountEnabled": true,
   *     "userPrincipalName": "John.Doe@portlandoregon.gov",
   *     "displayName": "Doe, John",
   *     "givenName": "John",
   *     "surname": "Doe",
   *     "jobTitle": "City worker",
   *     "mail": "John.Doe@portlandoregon.gov"
   * }
   */
  public function processItem($data)
  {
    foreach ($data["users"] as $user_data) {
      // Skip accounts without first name, last name, userPrincipalName, or email. These are not people acount.
      if (
        empty($user_data['givenName']) ||
        empty($user_data['surname']) ||
        empty($user_data['mail']) ||
        empty($user_data['userPrincipalName']) ||
        empty($user_data['id']) ||
        str_ends_with($user_data['userPrincipalName'], 'onmicrosoft.com') ||
        str_contains(strtolower($user_data['mail']), '_adm@')
      ) {
        continue;
      }

      // Look up user by Drupal user name (Principal name in AD)
      // Sometimes a user will be recreated with the same principal name but different AD ID
      // User name in Drupal has a limit of 60 characters
      $userName = PortlandOpenIdConnectUtil::TrimUserName($user_data['userPrincipalName']);
      $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $userName]);

      // In PGOV, skip the user if it does not exist
      if(empty($users)) return;

      /** @var User $user */
      $user = array_values($users)[0];
      $user->status = $user_data['accountEnabled'];
      $user->field_principal_name = $user_data['userPrincipalName'];
      $user->field_first_name = $user_data['givenName'];
      $user->field_last_name = $user_data['surname'];
      $user->field_full_name = $user_data['displayName'];
      $user->mail->value = $user_data['mail'];

      try {
        $user->save();
      } catch (Exception $e) {
        \Drupal::logger('portland OpenID')->error('Failed to save user ' . $user_data['userPrincipalName']);
      }
    }
  }
}
