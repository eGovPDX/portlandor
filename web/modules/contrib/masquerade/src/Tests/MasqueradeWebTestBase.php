<?php

namespace Drupal\masquerade\Tests;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;
use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\UserInterface;

/**
 * Base test class for Masquerade module web tests.
 *
 * @todo Core: $this->session_id is reset to NULL upon every internal browser
 *   request.
 * @see http://drupal.org/node/1555862
 */
abstract class MasqueradeWebTestBase extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['masquerade', 'user', 'block'];

  /**
   * Various users for the tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin_user, $auth_user, $editor_user, $masquerade_user, $moderator_user;

  /**
   * Various roles for the tests.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $admin_role, $editor_role, $masquerade_role, $moderator_role;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create and configure User module's admin role.
    $this->admin_role = Role::create([
      'id' => 'administrator',
      'label' => 'Administrator',
    ]);
    // Users in this role get all permissions assigned by default.
    $this->admin_role->set('is_admin', TRUE)->save();

    // Create a 'masquerade' role to masquerade as users without roles.
    $this->masquerade_role = Role::create([
      'id' => 'masquerade',
      'label' => 'Masquerade',
    ]);
    $this->masquerade_role
      // Allow only authenticated (without any other roles).
      ->grantPermission('masquerade as authenticated')
      ->save();

    // Create an additional 'editor' role to masquerade as basic users.
    $this->editor_role = Role::create([
      'id' => 'editor',
      'label' => 'Editor',
    ]);
    $this->editor_role
      ->grantPermission('masquerade as masquerade')
      ->grantPermission('masquerade as authenticated')
      ->save();

    // Create an additional 'moderator' role to check 'masquerade as any user'.
    $this->moderator_role = Role::create([
      'id' => 'moderator',
      'label' => 'Moderator',
    ]);
    $this->moderator_role
      ->grantPermission('masquerade as any user')
      ->save();

    // Create test users with varying privilege levels.

    // Administrative user with User module's admin role *only*.
    $this->admin_user = $this->drupalCreateUser();
    $this->admin_user->setUsername('admin_user');
    $this->admin_user->addRole($this->admin_role->id());
    $this->admin_user->save();

    // Moderator user.
    $this->moderator_user = $this->drupalCreateUser();
    $this->moderator_user->setUsername('moderator_user');
    $this->moderator_user->addRole($this->moderator_role->id());
    $this->moderator_user->save();

    // Editor user.
    $this->editor_user = $this->drupalCreateUser();
    $this->editor_user->setUsername('editor_user');
    $this->editor_user->addRole($this->editor_role->id());
    $this->editor_user->save();

    // Masquerade user.
    $this->masquerade_user = $this->drupalCreateUser();
    $this->masquerade_user->setUsername('masquerade_user');
    $this->masquerade_user->addRole($this->masquerade_role->id());
    $this->masquerade_user->save();

    // Authenticated user.
    $this->auth_user = $this->drupalCreateUser();

    // Place block to allow unmasquerade link accessible.
    $this->drupalPlaceBlock('system_menu_block:account');
  }

  /**
   * Masquerades as another user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account to masquerade as.
   */
  protected function masqueradeAs(UserInterface $account) {
    $this->drupalGet('user/' . $account->id());
    $this->clickLink(t('Masquerade as @name', ['@name' => $account->getDisplayName()]));
    // @todo Fix when crsfTokenSeed is avalable.
//    $this->drupalGet('user/' . $account->id() . '/masquerade', array(
//      'query' => array(
//        'token' => $this->drupalGetToken('user/' . $account->id() . '/masquerade'),
//      ),
//    ));
    $this->assertResponse(200);
    $this->assertText('You are now masquerading as ' . $account->label());

    // Update the logged in user account.
    // @see WebTestBase::drupalLogin()
    if (isset($this->session_id)) {
      $this->loggedInUser = $account;
      $this->loggedInUser->session_id = $this->session_id;
    }
  }

  /**
   * Unmasquerades the current user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account to unmasquerade from.
   */
  protected function unmasquerade(UserInterface $account) {
    $this->drupalGet('/user/' . $account->id());
    $this->clickLink('Unmasquerade');
    // @todo Fix when crsfTokenSeed is avalable.
//    $this->drupalGet('unmasquerade', array(
//      'query' => array(
//        'token' => $this->drupalGetToken('unmasquerade'),
//      ),
//    ));
    $this->assertResponse(200);
    $this->assertText('You are no longer masquerading as ' . $account->label());

    // Update the logged in user account.
    // @see WebTestBase::drupalLogin()
    if (isset($this->session_id)) {
      $this->loggedInUser = $account;
      $this->loggedInUser->session_id = $this->session_id;
    }
  }

  /**
   * Asserts that there is a session for a given user ID.
   *
   * @param int $uid
   *   The user ID for which to find a session record.
   * @param int|false $expected_masquerading_uid
   *   (optional) The expected value of the 'masquerading' session data. Pass
   *   FALSE to assert that the session data is not set.
   *
   * @return \stdClass
   *   The session record from {sessions}, if any.
   */
  protected function assertSessionByUid($uid, $expected_masquerading_uid = NULL) {
    $result = db_query('SELECT * FROM {sessions} WHERE uid = :uid', array(
      ':uid' => $uid,
    ))->fetchAll();
    if (empty($result)) {
      $this->fail(format_string('No session found for uid @uid', array('@uid' => $uid)));
    }
    elseif (count($result) > 1) {
      // If there is more than one session, then that must be unexpected.
      $this->fail("Found more than 1 session for uid $uid.");
    }
    else {
      $this->pass("Found session for uid $uid.");
      $session = reset($result);

      // Decode the session data.
      if (!empty($session->session)) {
        // Careful: PHP does not provide a utility function that decodes session
        // data only. session_decode() merges the input into the global
        // $_SESSION (but only if it is an array).
        // @see http://php.net/manual/function.session-decode.php
        $old_session = isset($_SESSION) ? $_SESSION : NULL;
        // Furthermore, if this test is executed on the command line, then
        // Drupal denies to start a session. PHP throws a notice if the session
        // is attempted to be started more than once.
        // @see drupal_session_start()
        @session_start();
        // In any case, ensure that it is empty.
        $_SESSION = array();

        if (!session_decode($session->session)) {
          $this->fail(format_string('Failed to decode session data: @data', array('@data' => $session->session)));
        }
        $session->session = isset($_SESSION) ? $_SESSION : array();

        // Restore the original global session.
        $_SESSION = NULL;
        if (isset($old_session)) {
          $_SESSION = $old_session;
        }
      }
      else {
        $session = new \stdClass();
        $session->session = array();
      }

      if (isset($expected_masquerading_uid)) {
        if ($expected_masquerading_uid !== FALSE) {
          $this->assertEqual($session->session['masquerading'], $expected_masquerading_uid, format_string('$_SESSION[\'masquerading\'] equals @uid.', array(
            '@uid' => $expected_masquerading_uid,
          )));
        }
        else {
          $this->assert(!isset($session->session['masquerading']), '$_SESSION[\'masquerading\'] is not set.');
        }
      }
      return $session;
    }
  }

  /**
   * Asserts that no session exists for a given uid.
   *
   * @param int $uid
   *   The user ID to assert.
   */
  protected function assertNoSessionByUid($uid) {
    $result = db_query('SELECT * FROM {sessions} WHERE uid = :uid', array(
      ':uid' => $uid,
    ))->fetchAll();
    $this->assert(empty($result), "No session for uid $uid found.");
  }

  /**
   * Stop-gap fix.
   *
   * @see http://drupal.org/node/1555862
   */
  protected function drupalGetToken($value = '') {
    // Use the same code as \Drupal\Core\Access\CsrfTokenGenerator::get().
    $private_key = $this->container->get('private_key')->get();
    /** @var \Drupal\Core\Session\MetadataBag $session_metadata */
    $session_metadata = $this->container->get('session_manager.metadata_bag');
    // @TODO Try to get seed from testing site, broken now.
    $seed = $session_metadata->getCsrfTokenSeed();
    return Crypt::hmacBase64($value, $seed . $private_key . Settings::getHashSalt());
  }

}

