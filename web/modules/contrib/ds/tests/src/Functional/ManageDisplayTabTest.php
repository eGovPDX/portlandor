<?php

namespace Drupal\Tests\ds\Functional;

/**
 * Tests for the manage display tab in Display Suite.
 *
 * @group ds
 */
class ManageDisplayTabTest extends FastTestBase {

  /**
   * Test tabs.
   */
  public function testFieldPlugin() {
    /* @var \Drupal\node\NodeInterface $node */
    $node = $this->entitiesTestSetup();

    // Verify we can see the manage display tab on a node and can click on it.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('Manage display');
    $this->assertSession()->responseContains('node/' . $node->id() . '/manage-display');
    $this->drupalGet('node/' . $node->id() . '/manage-display');

    // Verify we can see the manage display tab on a user and can click on it.
    $this->drupalGet('user/' . $this->adminUser->id());
    $this->assertSession()->responseContains('Manage display');
    $this->assertSession()->responseContains('user/' . $this->adminUser->id() . '/manage-display');
    $this->drupalGet('user/' . $this->adminUser->id() . '/manage-display');

    // Verify we can see the manage display tab on a taxonomy term and can click
    // on it.
    $this->drupalGet('taxonomy/term/1');
    $this->assertSession()->responseContains('Manage display');
    $this->assertSession()->responseContains('taxonomy/term/1/manage-display');
    $this->drupalGet('taxonomy/term/1/manage-display');
  }

}
