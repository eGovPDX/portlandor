<?php

namespace Drupal\portland_openid_connect\Util;

use GuzzleHttp\Client;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\File\FileSystemInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\user\Entity\User;
use Drupal\Core\TypedData\ListInterface;
use Drupal\taxonomy\TermStorageInterface;

/**
 * A helper class provides static function to allow both cron jobs
 * and views bulk operations to share common logic.
 */
class PortlandOpenIdConnectUtil
{
  public const ROSE_DOMAIN_NAME = "portlandoregon.gov";
  public const PTLD_DOMAIN_NAME = "police.portlandoregon.gov";

  // Keys
  private static $ROSE_SYNC_CLIENT_ID;
  private static $ROSE_SYNC_CLIENT_SECRET;
  private static $PTLD_SYNC_CLIENT_ID;
  private static $PTLD_SYNC_CLIENT_SECRET;

  /* @var \GuzzleHttp\ClientInterface $client */
  private static $client;

  public static function init() {
    if(empty(self::$ROSE_SYNC_CLIENT_ID))
      self::$ROSE_SYNC_CLIENT_ID = file_get_contents('sites/default/files/private/rose_sync_client_id.key');
    if(empty(self::$ROSE_SYNC_CLIENT_SECRET))
      self::$ROSE_SYNC_CLIENT_SECRET = file_get_contents('sites/default/files/private/rose_sync_client_secret.key');
    if(empty(self::$PTLD_SYNC_CLIENT_ID))
      self::$PTLD_SYNC_CLIENT_ID = file_get_contents('sites/default/files/private/ptld_sync_client_id.key');
    if(empty(self::$PTLD_SYNC_CLIENT_SECRET))
      self::$PTLD_SYNC_CLIENT_SECRET = file_get_contents('sites/default/files/private/ptld_sync_client_secret.key');
    if (empty(self::$client)) self::$client = new Client();
  }

  /**
   * Helper function to remove a user's Employee role from a group.
   * Only the Employee role is automatically added or removed.
   */
  public static function removeEmployeeRoleOnUserFromGroup($account, $group_id)
  {
    if(empty($account) || empty($group_id)) return;

    // Automated removal should only remove the "Employee" role
    $group = Group::load($group_id);
    $membership = $group->getMember($account);
    if (empty($membership)) return;

    $roles = $membership->getRoles(FALSE);
    $has_employee_role = false;
    $group_content = $membership->getGroupRelationship();
    $group_content->group_roles = [];
    foreach ($roles as $role) {
      if ($role->id() === 'employee-employee' || $role->id() === 'private-employee') {
        $has_employee_role = true;
        continue;
      }
      // Remove the "member" role
      // else if ($role->id() === 'employee-member') {
      //   $has_employee_role = true;
      //   continue;
      // }
      /** @var ListInterface $group_roles_list */
      $group_roles_list = $group_content->group_roles;
      $group_roles_list->appendItem(['target_id' => $role->id()]);
    }

    // // Hotfix: comment out to avoid removal of membership
    // If the user has no role in the group, remove the user completely
    // $group = \Drupal\group\Entity\Group::load($group_id);
    // if($group_content->group_roles->count() === 0) {
    //   $group->removeMember($account);
    //   $group->save();
    // }
    // // Else only remove the Employee roles. Keep roles like Following
    // else {
    //   $group_content->save();
    // }

    if($has_employee_role) {
      $group_content->save();
    }
  }

  /**
   * Helper function to add a user to a group with the Employee role
   */
  public static function addUserToGroupWithEmployeeRole($account, $group_id)
  {
    if(empty($account) || empty($group_id)) return;

    $group = Group::load($group_id);
    $role_id_array = [$group->getGroupType()->id() . '-employee'];
    $membership = $group->getMember($account);
    // The user is NOT in the group
    if (empty($membership)) {
      $group->addMember($account, ['group_roles' => $role_id_array]);
      $group->save();
    }
    // The user is already in the group, check if she has all roles specified in $role_id_array
    else {
      // https://drupal.stackexchange.com/questions/232530/programmatically-add-new-role-to-group-member/232646#232646
      // Array of Role-name=>Role_entity
      $roles = $membership->getRoles(FALSE);
      $group_content = $membership->getGroupRelationship();
      $has_new_role = false;
      foreach ($role_id_array as $role_id) {
        // Check if the user has the new role
        if (!isset($roles[$role_id])) {
          $group_content->group_roles->appendItem(['target_id' => $role_id]);
          $has_new_role = true;
        }
      }
      if ($has_new_role) $group_content->save();
    }
  }

  /**
   * Helper function to keep the Primary Groups field in sync with AD Group Names
   * Will be called by hook_user_presave.
   */
  public static function updatePrimaryGroupFieldForUser($account) {
    if (empty($account)) return;
    // Skip contact only users
    if($account->field_is_contact_only->value == 1) {
      $account->field_primary_groups = [];
    }
    else {
      $account->field_primary_groups = self::buildGroupIDlistFromGroupNames($account->field_group_names->value);
    }
    // DO NOT save the account. Only modify the field value.
  }

  /**
   * Helper function to keep a user's group membership in-sync with the AD primary group name.
   * Will be called by hook_user_update and hook_user_insert.
   */
  public static function updatePrimaryGroupMembershipForUser($account)
  {
    if (empty($account)) return;
    self::init();

    $new_primary_group_ids = self::buildGroupIDlistFromGroupNames($account->field_group_names->value);

    // Some AD group name does not have a matching Drupal group ID
    if(!empty($account->field_group_names->value) && empty($new_primary_group_ids)) return;

    if ($account->isNew()) {
      $current_primary_group_ids = [];
    } else {
      $current_primary_group_ids = self::getGroupIdsOfUser($account);
    }
    if (empty($new_primary_group_ids) && empty($current_primary_group_ids)) return;

    if (empty($new_primary_group_ids)) {
      // If the AD primary group is empty, remove employee role from group memberships
      foreach ($current_primary_group_ids as $current_primary_group_id) {
        self::removeEmployeeRoleOnUserFromGroup($account, $current_primary_group_id);
      }
    } else if (empty($current_primary_group_ids)) {
      // Add the employee role to user in all new groups
      foreach ($new_primary_group_ids as $new_primary_group_id) {
        self::addUserToGroupWithEmployeeRole($account, $new_primary_group_id);
      }
    } else {
      // For any added group, add membership
      foreach ($new_primary_group_ids as $new_primary_group_id) {
        self::addUserToGroupWithEmployeeRole($account, $new_primary_group_id);
      }

      // Check if any current group is not in the new group list
      foreach ($current_primary_group_ids as $current_primary_group_id) {
        if (in_array($current_primary_group_id, $new_primary_group_ids))
          continue;
        // Remove employee role from the group membership
        self::removeEmployeeRoleOnUserFromGroup($account, $current_primary_group_id);
      }
    }
  }

  /**
   * Convert a comma separated string of group names into group IDs
   * using taxonomy "Group AD name list"
   */
  private static function buildGroupIDlistFromGroupNames($group_names)
  {
    if(empty($group_names)) return;
    // Assume the group names have been cleaned by presave hook
    $group_names_array = explode(',', $group_names);

    // Build new primary group ID array with primary group names
    /** @var TermStorageInterface $term_storage */
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $group_ad_name_list = $term_storage->loadTree('group_ad_name_list', 0, 1, true);
    $group_ids = [];
    foreach ($group_ad_name_list as $group_ad_name) {
      if (in_array($group_ad_name->name->value, $group_names_array)) {
        $drupal_groups = $group_ad_name->field_employee_groups->getValue();
        foreach ($drupal_groups as $drupal_group) {
          $group_ids[] = $drupal_group['target_id'];
        }
      }
    }
    // Remove duplicates
    return array_unique($group_ids);
  }

  /**
   * Return an array of group IDs that the user belongs to
   */
  public static function getGroupIdsOfUser($account)
  {
    if(empty($account)) return;

    $group_ids = [];
    $grp_membership_service = \Drupal::service('group.membership_loader');
    $grps = $grp_membership_service->loadByUser($account);
    foreach ($grps as $grp) {
      $group_ids[] = $grp->getGroup()->id();
    }
    return $group_ids;
  }

  /**
   * Call Microsoft Azure AD OAuth API to retrieve the access token.
   * Need a fresh token for each CRON job run.
   */
  public static function GetAccessToken($domain = self::ROSE_DOMAIN_NAME)
  {
    self::init();
    $settings = [
      self::ROSE_DOMAIN_NAME => [
        "tenant_id" => '636d7808-73c9-41a7-97aa-8c4733642141',
        "client_id" =>  self::$ROSE_SYNC_CLIENT_ID,
        "client_secret" =>  self::$ROSE_SYNC_CLIENT_SECRET,
      ],
      self::PTLD_DOMAIN_NAME => [
        "tenant_id" => 'c365223a-f116-4a03-8077-94c3f5e5ca65',
        "client_id" =>  self::$PTLD_SYNC_CLIENT_ID,
        "client_secret" =>  self::$PTLD_SYNC_CLIENT_SECRET,
      ],
    ];

    static $token_expire_time = [
      self::ROSE_DOMAIN_NAME => 0,
      self::PTLD_DOMAIN_NAME => 0,
    ];
    static $tokens = [
      self::ROSE_DOMAIN_NAME => null,
      self::PTLD_DOMAIN_NAME => null,
    ];
    // If the token has not expired, return the previous token
    if (time() < ( $token_expire_time[$domain] - 300 )) return $tokens[$domain];

    $tenant_id = $settings[$domain]["tenant_id"];
    $request_options = [
      'form_params' => [
        'client_id' => $settings[$domain]["client_id"],
        'client_secret' => $settings[$domain]["client_secret"],
        'grant_type' => 'client_credentials',
        'scope' => 'https://graph.microsoft.com/.default',
      ],
    ];

    try {
      $response = self::$client->post("https://login.microsoftonline.com/$tenant_id/oauth2/v2.0/token", $request_options);
      $response_data = json_decode((string) $response->getBody(), TRUE);

      // Expected result.
      $tokens[$domain] = [
        'access_token' => $response_data['access_token'],
      ];
      if (array_key_exists('expires_in', $response_data)) {
        $tokens[$domain]['expire'] = \Drupal::time()->getRequestTime() + $response_data['expires_in'];
      }
      $token_expire_time[$domain] = time() + $response_data['expires_in'];
      return $tokens[$domain];
    } catch (RequestException $e) {
      $variables = [
        '@message' => 'Could not retrieve access token',
        '@error_message' => $e->getMessage(),
      ];
      \Drupal::logger('portland OpenID')->error('@message. Details: @error_message', $variables);
      return FALSE;
    }
  }

  public static function ShouldSkipUser($user) {
    // Skip ROSE users who are added in PTLD for special access
    if(
      str_ends_with($user->mail->value, "@portlandoregon.gov") &&
      str_ends_with($user->name->value, "@police.portlandoregon.gov")
    ) {
      return true;
    }

    // Skip users without both firstname and lastname
    if(
      empty($user->field_first_name->value) &&
      empty($user->field_last_name->value)
    ) {
      return true;
    }

    // Code below will convert these emails to lower case. OK to use upper case here.
    $skip_emails = [
      'BTS-eGov@portlandoregon.gov',
      'ally.admin@portlandoregon.gov',
      'marty.member@portlandoregon.gov',
      'oliver.outsider@portlandoregon.gov',
    ];
    $email = strtolower($user->mail->value);
    if( 
      $user->field_is_contact_only->value || // Skip contact only users
      empty($user->field_active_directory_id->value) || // Skip users without AD ID
      in_array($email, array_map('strtolower', $skip_emails)) || // Skip users with certain email
      str_ends_with($email, 'onmicrosoft.com') ||  // Skip users with generic email
      str_contains($email, '_adm@')
    ) {
      return true;
    }
    return false;
  }

  /**
   * Drupal username is limited to 60 characters. Trim the principal name if it's too long.
   */
  public static function TrimUserName($userName)
  {
    if(empty($userName) || strlen($userName) <= 60) {
      return $userName;
    }

    // Leave 3 characters for a random number to reduce conflict
    if(str_contains($userName, "@")) {
      $parts = explode("@", $userName);
      return hash("md5", $parts[0]) . "@" . $parts[1];
    }
    else {
      return hash("md5", $userName);
    }
  }

  /**
   * Retrieve user profile and update user's field values. 
   * Does NOT save user.
   */
  public static function GetUserProfile($access_token, $user)
  {
    if (empty($access_token) || empty($user)) return false;
    self::init();

    // Some users should be skipped
    if (self::ShouldSkipUser($user)) return false;

    try {
      $mail = str_replace("'", "", $user->mail->value);
      // API Document: https://docs.microsoft.com/en-us/graph/api/resources/profile-example?view=graph-rest-beta
      $response = self::$client->get(
        "https://graph.microsoft.com/beta/users/$mail/profile", // Must use email for beta user profile API
        [
          'method' => 'GET',
          'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $access_token,
          ],
        ]
      );
      $response_data = json_decode((string) $response->getBody(), TRUE);
      $user_info = [];
      if (
        array_key_exists('positions', $response_data) &&
        !empty($response_data["positions"]) &&
        array_key_exists('detail', $response_data["positions"][0])
      ) {
        $user_info['title'] = $response_data["positions"][0]["detail"]["jobTitle"];
        $user_info['group'] = $response_data["positions"][0]["detail"]["company"]["displayName"];
        $user_info['division'] = $response_data["positions"][0]["detail"]["company"]["department"];
        $user_info['officeLocation'] = $response_data["positions"][0]["detail"]["company"]["officeLocation"];
        $address = $response_data["positions"][0]["detail"]["company"]["address"];
        $address_parts = [];
        if( !empty($address['street']) ) $address_parts[] = $address['street'];
        if( !empty($address['city']) ) $address_parts[] = $address['city'];
        if( !empty($address['state']) ) $address_parts[] = $address['state'];
        if( !empty($address['postalCode']) ) $address_parts[] = $address['postalCode'];
        $user_info['address'] = implode(', ', $address_parts);

        // Get phone numbers
        $user_info['phone'] = '';
        $phones = $response_data["phones"];
        foreach($phones as $phone) {
          if($phone['type'] == 'business') {
            $user_info['phone'] = $phone['number'];
          }
          else if($phone['type'] == 'mobile') {
            $user_info['mobile_phone'] = $phone['number'];
          }
        }

        $user_info['principalName'] = $response_data["account"][0]['userPrincipalName'];
        $user_info['first_name'] = $response_data['names'][0]['first'];
        $user_info['last_name'] = $response_data['names'][0]['last'];
      }

      // Look up Drupal user with email
      // $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail' => $email]);
      // $user = array_values($users)[0]; // Assume the lookup returns only one unique user.
      $user->field_principal_name = $user_info['principalName'];
      $user->field_first_name = $user_info['first_name'];
      $user->field_last_name = $user_info['last_name'];
      $user->field_title = $user_info['title'];
      $user->field_division_name = $user_info['division'];
      $user->field_office_location = $user_info['officeLocation'];
      $user->field_address = $user_info['address'];
      $user->field_phone = $user_info['phone'];
      $user->field_mobile_phone = array_key_exists('mobile_phone', $user_info) ? $user_info['mobile_phone'] : '';
      $user->field_group_names = $user_info['group'];
      $user->setUsername( self::TrimUserName($user_info['principalName']) );
      return true;
    } catch (RequestException $e) {
      // Log a notice when the user's profile can't be retrieved but do not disable the user.
      $variables = [
        '@message' => 'Could not retrieve info for principal name ' . $user->getAccountName(),
        '@error_message' => $e->getMessage(),
      ];
      \Drupal::logger('portland OpenID')->notice('@message. Details: @error_message', $variables);
      return false;
    }
  }

  /**
   * Get the user's manager
   */
  public static function GetUserManager($access_token, $user)
  {
    if (empty($access_token) || empty($user)) return;
    self::init();

    // PTLD has no manager info
    if(str_ends_with($user->mail->value, self::PTLD_DOMAIN_NAME)) return;
    // Must use Principal Name to look up manager
    $user_lookup_key = $user->field_principal_name->value ?? $user->name->value;
    try {
      // https://learn.microsoft.com/en-us/graph/api/user-list-manager?view=graph-rest-1.0&tabs=http
      $response = self::$client->get(
        "https://graph.microsoft.com/v1.0/users/$user_lookup_key/manager", // Must use Principal Name for v1.0 API
        [
          'method' => 'GET',
          'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $access_token,
          ],
        ]
      );
      $response_data = json_decode((string) $response->getBody(), TRUE);
/*
{
    "@odata.context": "https://graph.microsoft.com/v1.0/$metadata#directoryObjects/$entity",
    "@odata.type": "#microsoft.graph.user",
    "id": "d8f3158f-2589-4b3d-86e4-d09c048c6635",
    "businessPhones": [],
    "displayName": "Last, First",
    "givenName": "First",
    "mail": "EMAIL",
    "surname": "Last",
    "userPrincipalName": "PRINCIPAL_NAME"
}
*/
      if (
        array_key_exists('@odata.type', $response_data) &&
        $response_data["@odata.type"] === "#microsoft.graph.user"
      ) {
        // Try to load the Drupal user with AD ID
        $manager_ad_id = $response_data['id'];
        $manager_users = \Drupal::entityTypeManager()->getStorage('user')
          ->loadByProperties(['field_active_directory_id' => $manager_ad_id]);

        $manager_user_ids = [];
        if (count($manager_users) > 0) {
          // manager_users is an associate array with user ID as key, user entity as value
          $manager_user_ids[] = key($manager_users);
          // \Drupal::logger('portland OpenID')->notice('Found existing manager: ' . $manager_ad_id);
        } else {
          $manager_stub_user = User::create([
            'name' => self::TrimUserName($response_data['userPrincipalName']),
            'mail' => $response_data['mail'],
            'pass' => \Drupal::service('password_generator')->generate(), // temp password
            'status' => 1,
            'field_first_name' => $response_data['givenName'],
            'field_last_name' => $response_data['surname'],
            'field_active_directory_id' => $manager_ad_id,
            'field_principal_name' => $response_data['userPrincipalName'],
          ]);
          $manager_stub_user->save();
          $manager_user_ids[] = $manager_stub_user->id();
        }
        $user->set('field_managers', array_unique($manager_user_ids));
      }
    } catch (RequestException $e) {
      $variables = [
        '@message' => 'Cannot retrieve user\'s manager information for principal name ' . $user->getAccountName(),
        '@error_message' => $e->getMessage(),
      ];
      \Drupal::logger('portland OpenID')->debug('@message. Details: @error_message', $variables);
    }
  }

  /**
   * Get a user's photo.
   */
  public static function GetUserPhoto($access_token, $userPrincipalName, $azure_ad_id)
  {
    if (empty($access_token) || empty($userPrincipalName) || empty($azure_ad_id)) return;
    self::init();

    // Perform the request.
    $options = [
      'method' => 'GET',
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $access_token,
      ],
    ];

    try {
      // API Document: https://docs.microsoft.com/en-us/graph/api/resources/profile-example?view=graph-rest-beta
      // Example: https://graph.microsoft.com/v1.0/users/xinju.wang@portlandoregon.gov/photo/$value
      $response = self::$client->get(
        'https://graph.microsoft.com/v1.0/users/' . $azure_ad_id . '/photo/$value',
        $options
      );
      $file_name = str_replace('@', '_', $userPrincipalName);
      $file_name = str_replace('.', '_', $file_name);
      $user_photo_folder_name = "public://user-photo";
      \Drupal::service('file_system')->prepareDirectory(
        $user_photo_folder_name,
        FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
      );
      $user_photo_file = \Drupal::service('file.repository')->writeData((string) $response->getBody(), 'public://user-photo/' . $file_name . '.jpg', FileSystemInterface::EXISTS_REPLACE);

      $users = \Drupal::entityTypeManager()->getStorage('user')
        ->loadByProperties(['name' => $userPrincipalName]);
      if (count($users) != 0) {
        $user = array_values($users)[0]; // Assume the lookup returns only one unique user.
        $user_display_name = $user->field_first_name->value . ' ' . $user->field_last_name->value;
        $user->user_picture->setValue(
          [
            'target_id' => $user_photo_file->id(),
            'alt'       => $user_display_name . ' profile picture',
            'title'     => $user_display_name,
          ]
        );
        $user->save();
      }
    } catch (RequestException $e) {
      // Do not log 404 errors since some users don't have pictures
      if ($e->getCode() != 404) {
        $variables = [
          '@message' => 'Could not retrieve user picture for principal name ' . $userPrincipalName,
          '@error_message' => $e->getMessage(),
        ];
        \Drupal::logger('portland OpenID')->notice('@message. Details: @error_message', $variables);
      }
    }
  }

  public static function GetUserLookupKey($user) {
    // Use Active Directory Object ID first, Principal Name next, and email as the last resort.
    $user_lookup_key = $user->field_active_directory_id->value ?? $user->field_principal_name->value;
    if(empty($user_lookup_key)) $user_lookup_key = $user->name->value;
    if(empty($user_lookup_key)) $user_lookup_key = $user->mail->value;
    return $user_lookup_key;
  }

  /**
   * Check if a user account is enabled in Azure AD.
   * Call https://graph.microsoft.com/beta/users/USER_PRINCIPAL_NAME or UUID to check the value of "accountEnabled" field.
   */
  public static function IsUserEnabled($access_token, $user)
  {
    // Avoid disabling user accidentally
    if (empty($access_token) || empty($user)) return true;
    self::init();

    $user_lookup_key = PortlandOpenIdConnectUtil::GetUserLookupKey($user);
    try {
      // Example: https://graph.microsoft.com/beta/users/PRINCIPAL_NAME
      $response = self::$client->get(
        "https://graph.microsoft.com/beta/users/$user_lookup_key",
        [
          'method' => 'GET',
          'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $access_token,
          ],
        ]
      );
      $response_data = json_decode((string) $response->getBody(), TRUE);
      return $response_data["accountEnabled"];
    } catch (RequestException $e) {
      // Treat 404 as the user doesn't exist in AD. Do not log 404
      if ($e->getCode() == 401) {
        \Drupal::logger('portland OpenID')->error("Invalid access token. Please verify client secret is valid for " . $user->mail->value);
        return null; 
      }
      // Treat 404 as the user doesn't exist in AD. Do not log 404
      else if ($e->getCode() == 404) {
        return false;
      }
      else {
        $variables = [
          '@message' => 'Could not retrieve info for user ' . $user->getAccountName(),
          '@error_message' => $e->getMessage(),
        ];
        \Drupal::logger('portland OpenID')->notice('@message. Details: @error_message', $variables);
        return false;
      }
      return false;
    }
  }

  /**
   * Disable a user and clear certain fields
   */
  public static function DisableUser($user)
  {
    if( !$user ) return;

    $user->status->value = false;
    $user->field_title = "";
    $user->field_division_name = "";
    $user->field_office_location = "";
    $user->field_address = "";
    $user->field_phone = "";
    $user->field_mobile_phone = "";
    $user->set('field_managers', []);
    $user->save();
    \Drupal::logger('portland OpenID')->notice('User ' . $user->getAccountName() . ' has been disabled.');
  }

  /**
   * Enable a user
   */
  public static function EnableUser($user)
  {
    if( !$user ) return;

    $user->status->value = true;
    $user->save();
    \Drupal::logger('portland OpenID')->notice('User ' . $user->getAccountName() . ' has been enabled.');
  }
}
