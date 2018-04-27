<?php

namespace Drupal\masquerade\Tests;

/**
 * Tests masquerade access mechanism.
 *
 * @todo Convert into DUTB. This is essentially a unit test for
 *   masquerade_target_user_access() only.
 *
 * @group masquerade
 */
class MasqueradeAccessTest extends MasqueradeWebTestBase {

  /**
   * Tests masquerade access for different source and target users.
   *
   * Test plan summary:
   * - root » admin
   * - admin » root
   * - admin » moderator (more roles but less privileges)
   * - admin » masquerade (different role)
   * - admin » auth (less roles)
   * - moderator ! root
   * - moderator ! admin (less roles but more privileges)
   * - moderator ! editor (different roles + privileges)
   * - moderator » masquerade (less roles)
   * - moderator » auth
   * - [editor is access-logic-wise equal to moderator, so skipped]
   * - masquerade ! root
   * - masquerade ! admin (different role with more privileges)
   * - masquerade ! moderator (more roles)
   * - masquerade » auth
   * - masquerade ! masquerade (self)
   * - auth ! *
   */
  public function testAccess() {
    $this->drupalLogin($this->rootUser);
    $this->assertCanMasqueradeAs($this->admin_user);

    $this->drupalLogin($this->admin_user);
    // Permission 'masquerade as super user' granted by default.
    $this->assertCanMasqueradeAs($this->rootUser);
    // Permission 'masquerade as any user' granted by default.
    $this->assertCanMasqueradeAs($this->moderator_user);
    $this->assertCanMasqueradeAs($this->editor_user);
    $this->assertCanMasqueradeAs($this->masquerade_user);
    $this->assertCanMasqueradeAs($this->auth_user);

    // Test 'masquerade as any user' permission except UID 1.
    $this->drupalLogin($this->moderator_user);
    $this->assertCanNotMasqueradeAs($this->rootUser);
    $this->assertCanMasqueradeAs($this->admin_user);
    $this->assertCanMasqueradeAs($this->editor_user);
    $this->assertCanMasqueradeAs($this->masquerade_user);
    $this->assertCanMasqueradeAs($this->auth_user);

    // Test 'masquerade as @role' permission.
    $this->drupalLogin($this->editor_user);
    $this->assertCanNotMasqueradeAs($this->rootUser);
    $this->assertCanNotMasqueradeAs($this->admin_user);
    $this->assertCanNotMasqueradeAs($this->moderator_user);
    $this->assertCanMasqueradeAs($this->masquerade_user);
    $this->assertCanMasqueradeAs($this->auth_user);

    // Test 'masquerade as authenticated' permission.
    $this->drupalLogin($this->masquerade_user);
    $this->assertCanNotMasqueradeAs($this->rootUser);
    $this->assertCanNotMasqueradeAs($this->admin_user);
    $this->assertCanNotMasqueradeAs($this->moderator_user);
    $this->assertCanNotMasqueradeAs($this->editor_user);
    $this->assertCanMasqueradeAs($this->auth_user);

    // Verify that a user cannot masquerade as himself.
    $edit = [
      'masquerade_as' => $this->masquerade_user->getAccountName(),
    ];
    $this->drupalPostForm('masquerade', $edit, t('Switch'));
    $this->assertRaw(t('You cannot masquerade as yourself. Please choose a different user to masquerade as.'));
    $this->assertNoText(t('Unmasquerade'));

    // Basic 'masquerade' permission check.
    $this->drupalLogin($this->auth_user);
    $this->drupalGet('masquerade');
    $this->assertResponse(403);
  }

  /**
   * Asserts that the logged-in user can masquerade as a given target user.
   *
   * @param \Drupal\user\UserInterface $target_account
   *   The user to masquerade to.
   */
  protected function assertCanMasqueradeAs($target_account) {
    $edit = [
      'masquerade_as' => $target_account->getAccountName(),
    ];
    $this->drupalPostForm('masquerade', $edit, t('Switch'));
    $this->assertNoRaw(t('You are not allowed to masquerade as %name.', [
      '%name' => $target_account->getDisplayName(),
    ]));
    $this->clickLink(t('Unmasquerade'));
  }

  /**
   * Asserts that the logged-in user can not masquerade as a given target user.
   *
   * @param \Drupal\user\UserInterface $target_account
   *   The user to masquerade to.
   */
  protected function assertCanNotMasqueradeAs($target_account) {
    $edit = [
      'masquerade_as' => $target_account->getAccountName(),
    ];
    $this->drupalPostForm('masquerade', $edit, t('Switch'));
    $this->assertRaw(t('You are not allowed to masquerade as %name.', [
      '%name' => $target_account->getDisplayName(),
    ]));
    $this->assertNoText(t('Unmasquerade'));
  }

}
