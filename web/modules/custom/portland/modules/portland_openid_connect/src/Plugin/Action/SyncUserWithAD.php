<?php

namespace Drupal\portland_openid_connect\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Deactivate users who are removed from AD.
 *
 * @Action(
 *   id = "sync_user_with_ad",
 *   label = @Translation("Block (disable) users based on AD status"),
 *   type = "user"
 * )
 */
class SyncUserWithAD extends ActionBase
{
  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL)
  {
    if (empty($account)) return;

    $skip_emails = [
      'BTS-eGov@portlandoregon.gov',
      'ally.admin@portlandoregon.gov',
      'marty.member@portlandoregon.gov',
      'amy.archer-masters@portlandoregon.gov',
      'amy.archer@portlandoregon.gov',
    ];
    if( in_array($account->getEmail(), $skip_emails)) return;

    $tokens = SyncUserWithAD::GetAccessToken();
    if (empty($tokens) || empty($tokens['access_token'])) {
      \Drupal::logger('portland OpenID')->error("Cannot retrieve access token for Microsoft Graph. Make sure the client secret is correct.");
      return;
    }

    return $this->GetADUserByEmail($tokens['access_token'], $account->getEmail());
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE)
  {
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->status->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * Get AD user info by email
   */
  public function GetADUserByEmail($access_token, $email)
  {
    if (empty($access_token) || empty($email)) return;

    /* @var \GuzzleHttp\ClientInterface $client */
    $client = new \GuzzleHttp\Client();
    // Perform the request.
    $options = [
      'method' => 'GET',
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $access_token,
      ],
    ];

    $found_user_in_AD = false;
    try {
      $response = $client->get('https://graph.microsoft.com/v1.0/users/' . $email, $options);

      $response_data = json_decode((string) $response->getBody(), TRUE);
      // If we cannot find the user in AD by email, assume we need to deactivate the user in Drupal
      $found_user_in_AD = isset($response_data['mail']);
    } catch (RequestException $e) {
    }

    if (!$found_user_in_AD) {
      // Find the user with email
      $users = \Drupal::entityTypeManager()->getStorage('user')
        ->loadByProperties(['mail' => $email]);

      if (count($users) != 0) {
        $user = array_values($users)[0]; // Assume the lookup returns only one unique user.
        $user->status = 0;
        $user->save();
        \Drupal::logger('portland OpenID')->notice('User deactivated: ' . $user->mail->value);
      }

      $variables = [
        '@message' => 'Could not retrieve user information',
        '@error_message' => $e->getMessage(),
      ];
      \Drupal::logger('portland OpenID')->error('@message. Details: @error_message', $variables);
      return $this->t('User deactivated');
    }
  }

  /**
   * Call Microsoft Azure AD OAuth API to retrieve the access token.
   * Need a fresh token for each CRON job run.
   */
  public static function GetAccessToken()
  {
    static $token_expire_time = 0;
    static $tokens = null;
    // If the token has not expired, return the previous token
    if (time() < $token_expire_time) return $tokens;

    $windows_aad_config = \Drupal::config('openid_connect.settings.windows_aad');
    $client_id = $windows_aad_config->get('settings.client_id');
    $tenant_id = '636d7808-73c9-41a7-97aa-8c4733642141';

    $request_options = [
      'form_params' => [
        // 'code' => $authorization_code,
        'client_id' => $client_id,
        'client_secret' => $windows_aad_config->get('settings.client_secret'),
        'grant_type' => 'client_credentials',
        'scope' => 'https://graph.microsoft.com/.default',
      ],
    ];

    /* @var \GuzzleHttp\ClientInterface $client */
    $client = new \GuzzleHttp\Client();

    try {
      $response = $client->post("https://login.microsoftonline.com/$tenant_id/oauth2/v2.0/token", $request_options);
      $response_data = json_decode((string) $response->getBody(), TRUE);

      // Expected result.
      $tokens = [
        // 'id_token' => $response_data['id_token'],
        'access_token' => $response_data['access_token'],
      ];
      if (array_key_exists('expires_in', $response_data)) {
        $tokens['expire'] = \Drupal::time()->getRequestTime() + $response_data['expires_in'];
      }
      $token_expire_time = time() + $response_data['expires_in'];
      return $tokens;
    } catch (RequestException $e) {
      $variables = [
        '@message' => 'Could not retrieve access token',
        '@error_message' => $e->getMessage(),
      ];
      \Drupal::logger('portland OpenID')->error('@message. Details: @error_message', $variables);
      return FALSE;
    }
  }
}
