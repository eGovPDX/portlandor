<?php

namespace Drupal\portland\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\portland_openid_connect\Util\PortlandOpenIdConnectUtil;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

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
   *   "user_type" => "drupal" or "entra_id"
   *   "users" => users[] // Drupal User objects for "drupal" user type or PHP Arrays for "entra_id" user type
   * ]
   */
  public function processItem($data)
  {
    if($data["user_type"] === "drupal") {
      $this->processDrupalUsers($data);
    }
    else if($data["user_type"] === "entra_id") {
      $this->processEntraIDUsers($data);
    }
  }

  public function processDrupalUsers($data) {


    $users_disabled = [];
    /* @var \GuzzleHttp\ClientInterface $client */
    $client = new Client();
    // Perform the request.
    $options = [
      'method' => 'GET',
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $data["access_token"],
        'ConsistencyLevel' => 'eventual',
      ],
    ];
    $skip_emails = [
      'BTS-eGov@portlandoregon.gov',
      'ally.admin@portlandoregon.gov',
      'marty.member@portlandoregon.gov',
      'oliver.outsider@portlandoregon.gov',
    ];

    foreach ($data["users"] as $user) {
      if( $user->field_is_distribution_list->value ) continue;
      if (in_array(strtolower($user->getEmail()), array_map('strtolower', $skip_emails))) continue;

      $request_url = 'https://graph.microsoft.com/v1.0/users?$count=true&$search="mail:' . $user->getEmail() . '"';
      $found_user_in_AD = false;
      try {
        $response = $client->get($request_url, $options);
        $response_data = json_decode((string) $response->getBody(), TRUE);
        // If we cannot find the user in AD by email, assume we need to block the user in Drupal
        $found_user_in_AD = isset($response_data['@odata.count']) && ($response_data['@odata.count'] > 0);
      } catch (RequestException $e) {
        $variables = [
          '@message' => 'Error retrieving user ' . $user->getEmail(),
          '@error_message' => $e->getMessage(),
        ];
        \Drupal::logger('portland OpenID')->error('@message. Details: @error_message', $variables);
      }

      if(!$found_user_in_AD && $user->status->value == 1) {
        $user->status = 0;
        $user->save();
        $users_disabled []= $user->getEmail();
      }
    }

    if(count($users_disabled) > 0) \Drupal::logger('portland OpenID')->info("Disabled " . count($users_disabled) . " users: " . implode(",", $users_disabled));
  }

  public function processEntraIDUsers($data) {
    $users_enabled = [];
    $users_disabled = [];
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
      if(empty($users)) {
        continue;
      }

      /** @var User $user */
      $user = array_values($users)[0];
      if($user_data['accountEnabled'] == 1 && $user->status->value == 0) $users_enabled []= $userName;
      if($user_data['accountEnabled'] == 0 && $user->status->value == 1) $users_disabled []= $userName;
      $user->status = $user_data['accountEnabled'];
      $user->field_principal_name = $user_data['userPrincipalName'];
      $user->field_active_directory_id = $user_data['id'];
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

    if(count($users_enabled) > 0) \Drupal::logger('portland OpenID')->info("Enabed " . count($users_enabled) . " users: " . implode(",", $users_enabled));
    if(count($users_disabled) > 0) \Drupal::logger('portland OpenID')->info("Disabled " . count($users_disabled) . " users: " . implode(",", $users_disabled));
  }
}
