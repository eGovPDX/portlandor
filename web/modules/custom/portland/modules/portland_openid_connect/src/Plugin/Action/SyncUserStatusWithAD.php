<?php

namespace Drupal\portland_openid_connect\Plugin\Action;

use GuzzleHttp\Client;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Access\AccessResult;
use Drupal\portland_openid_connect\Util\PortlandOpenIdConnectUtil;

/**
 * Synchronize user status with Azure AD.
 *
 * @Action(
 *   id = "sync_user_status_with_ad",
 *   label = @Translation("Synchronize user status with AD"),
 *   type = "user"
 * )
 */
class SyncUserStatusWithAD extends ActionBase
{
  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL)
  {
    if (empty($account)) return;

    // Skip if the Drupal user is marked as "Is Distribution List"
    $users = \Drupal::entityTypeManager()->getStorage('user')
      ->loadByProperties(['mail' => $account->getEmail()]);
    if (empty($users)) return;
    if (array_values($users)[0]->field_is_distribution_list->value) return;

    // Never block these users
    $user_email = $account->getEmail();
    $skip_emails = [
      'BTS-eGov@portlandoregon.gov',
      'ally.admin@portlandoregon.gov',
      'marty.member@portlandoregon.gov',
      'oliver.outsider@portlandoregon.gov',
      // 'amy.archer-masters@portlandoregon.gov',  // User email address
      // 'amy.archer@portlandoregon.gov',  // Principal user name
      // 'WBUDFTeam@portlandoregon.gov',  // Outlook distribution list
      // 'council140@portlandoregon.gov',  // Actual AD group
    ];
    if (in_array(strtolower($user_email), array_map('strtolower', $skip_emails))) return;

    // Extract domain from email address
    if (substr_count($user_email, "@") === 1) {
      list(, $domain) = explode("@", $user_email);
    } else {
      \Drupal::logger('portland OpenID')->error("Invalid email: $user_email");
      return;
    }
    if(empty($domain)) {
      \Drupal::logger('portland OpenID')->error("Cannot extract domain from email: $user_email");
      return;
    }

    $tokens = PortlandOpenIdConnectUtil::GetAccessToken($domain);
    if (empty($tokens) || empty($tokens['access_token'])) {
      \Drupal::logger('portland OpenID')->error("Cannot retrieve access token for Microsoft Graph. Make sure the client secret is correct.");
      return;
    }

    return $this->GetADUserByEmail($tokens['access_token'], $account->getEmail());
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE)
  {
    // The update permission's name could be either Update or Edit, we check both and allow access if either one is allowed.
    $access_result_for_update = $object->access('update', $account);
    $access_result_for_edit = $object->access('edit', $account);
    if ($access_result_for_update || $access_result_for_edit) {
      return ($return_as_object ? AccessResult::allowed() : true);
    } else {
      return ($return_as_object ? AccessResult::forbidden() : false);
    }
  }

  /**
   * Get AD user info by email
   */
  public function GetADUserByEmail($access_token, $email)
  {
    if (empty($access_token) || empty($email)) return;

    /* @var \GuzzleHttp\ClientInterface $client */
    $client = new Client();
    // Perform the request.
    $options = [
      'method' => 'GET',
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $access_token,
        'ConsistencyLevel' => 'eventual', // required by Graph search API
      ],
    ];

    $found_user_in_AD = false;
    try {
      // API Document: https://docs.microsoft.com/en-us/graph/query-parameters?context=graph%2Fapi%2F1.0&view=graph-rest-1.0
      // Example: https://graph.microsoft.com/v1.0/users?$count=true&$search="mail:xinju.wang@portlandoregon.gov"
      $response = $client->get(
        'https://graph.microsoft.com/v1.0/users?$count=true&$search="mail:' . $email . '"',
        $options
      );

      $response_data = json_decode((string) $response->getBody(), TRUE);
      // If we cannot find the user in AD by email, assume we need to block the user in Drupal
      $found_user_in_AD = isset($response_data['@odata.count']) && ($response_data['@odata.count'] > 0);
    } catch (RequestException $e) {
      $variables = [
        '@message' => 'Could not retrieve user information',
        '@error_message' => $e->getMessage(),
      ];
      \Drupal::logger('portland OpenID')->error('@message. Details: @error_message', $variables);
    }

    // If a user is found in AD, activate the user if she's inactive
    if ($found_user_in_AD) {
      // Load the Drupal user with email
      $users = \Drupal::entityTypeManager()->getStorage('user')
        ->loadByProperties(['mail' => $email]);

      if (count($users) != 0) {
        $user = array_values($users)[0]; // Assume the lookup returns only one unique user.
        if (! $user->status->value) {
          $user->status = 1;
          $user->save();
          \Drupal::logger('portland OpenID')->notice('User activated: ' . $user->mail->value);
        }
      }
      return $this->t('User activated');
    }
    // If a user is NOT found in AD, deactivate the user if she's active
    else {
      // Load the Drupal user with email
      $users = \Drupal::entityTypeManager()->getStorage('user')
        ->loadByProperties(['mail' => $email]);

      if (count($users) != 0) {
        $user = array_values($users)[0]; // Assume the lookup returns only one unique user.
        if ($user->status->value) {
          $user->status = 0;
          $user->save();
          \Drupal::logger('portland OpenID')->notice('User blocked: ' . $user->mail->value);
        }
      }
      return $this->t('User blocked');
    }
  }
}
