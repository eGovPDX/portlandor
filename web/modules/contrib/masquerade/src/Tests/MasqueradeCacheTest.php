<?php

namespace Drupal\masquerade\Tests;

/**
 * Tests caching for masquerade.
 *
 * @group masquerade
 */
class MasqueradeCacheTest extends MasqueradeWebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'masquerade',
    'user',
    'block',
    'node',
    'dynamic_page_cache',
  ];

  /**
   * Tests caching for the user switch block.
   */
  public function testMasqueradeSwitchBlockCaching() {
    // Create two masquerade users.
    $umberto = $this->drupalCreateUser([
      'masquerade as any user',
      'access content',
    ], 'umberto');
    $nelle = $this->drupalCreateUser([
      'masquerade as any user',
      'access content',
    ], 'nelle');

    // Add the Masquerade block to the sidebar.
    $masquerade_block = $this->drupalPlaceBlock('masquerade');

    // Login as Umberto.
    $this->drupalLogin($umberto);
    $this->drupalGet('<front>');
    $this->assertBlockAppears($masquerade_block);

    // Masquerade as Nelle.
    $edit = ['masquerade_as' => $nelle->getAccountName()];
    $this->drupalPostForm(NULL, $edit, t('Switch'));
    $this->drupalGet('<front>');
    $this->assertNoBlockAppears($masquerade_block);

    // Logout, and log in as Nelle.
    $this->drupalLogout();
    $this->drupalLogin($nelle);
    $this->drupalGet('<front>');
    $this->assertBlockAppears($masquerade_block);
  }

}
