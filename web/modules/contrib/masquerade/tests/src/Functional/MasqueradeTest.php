<?php

namespace Drupal\Tests\masquerade\Functional;

/**
 * Tests form permissions and user switching functionality.
 *
 * @group masquerade
 */
class MasqueradeTest extends MasqueradeWebTestBase {

  /**
   * Tests masquerade user links.
   */
  public function testMasquerade() {
    $this->drupalLogin($this->admin_user);

    // Verify that a token is required.
    $this->drupalGet('user/0/masquerade');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('user/' . $this->auth_user->id() . '/masquerade');
    $this->assertSession()->statusCodeEquals(403);

    // Verify that the admin user is able to masquerade.
    $this->assertSessionByUid($this->admin_user->id());
    $this->masqueradeAs($this->auth_user);
    $this->assertSessionByUid($this->auth_user->id(), $this->admin_user->id());
    $this->assertNoSessionByUid($this->admin_user->id());

    // Verify that a token is required to unmasquerade.
    $this->drupalGet('unmasquerade');
    $this->assertSession()->statusCodeEquals(403);

    // Verify that the web user cannot masquerade.
    $this->drupalGet('user/' . $this->admin_user->id() . '/masquerade', [
      'query' => [
        'token' => $this->drupalGetToken('user/' . $this->admin_user->id() . '/masquerade'),
      ],
    ]);
    $this->assertSession()->statusCodeEquals(403);

    // Verify that the user can unmasquerade.
    $this->unmasquerade($this->auth_user);
    $this->assertNoSessionByUid($this->auth_user->id());
    $this->assertSessionByUid($this->admin_user->id());
  }

}
