<?php

namespace Drupal\Tests\subpathauto\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Class SubPathautoFunctionalTest
 * @group subpathauto
 */
class SubPathautoFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['subpathauto', 'node', 'user', 'block', 'text', 'language'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->drupalCreateNode();

    ConfigurableLanguage::create(['id' => 'fi'])->save();

    // In order to reflect the changes for a multilingual site in the container
    // we have to rebuild it.
    $this->rebuildContainer();

    $alias_storage = $this->container->get('path.alias_storage');
    $alias_storage->save('/node/1', '/kittens');
    $alias_white_list = $this->container->get('path.alias_whitelist');
    $alias_white_list->set('node', TRUE);

    $admin_user = $this->drupalCreateUser([
      'bypass node access',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Ensures that inbound and outbound paths are converted correctly.
   */
  public function testBasicIntegration() {
    $this->drupalGet('/kittens');
    $this->assertSession()->linkByHrefExists('/kittens/edit', 0, 'Local task link path that is subpath for an alias lead to correct URL.');

    $this->clickLink('Edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists('/kittens', 0, 'Local task link with alias lead to correct URL.');
    $this->assertSession()->linkByHrefExists('/kittens/delete', 0, 'Local task link path that is subpath for an alias lead to correct URL.');
  }

  /**
   * Ensures that language prefix is handled correctly.
   */
  public function testWithLanguagePrefix() {
    $this->drupalGet('/fi/kittens');
    $this->assertSession()->linkByHrefExists('/fi/kittens/edit', 0, 'Local task link path that is subpath for an alias lead to correct URL when language prefix exists.');

    $this->clickLink('Edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists('/fi/kittens', 0, 'Local task link with alias lead to correct URL when language prefix exists..');
    $this->assertSession()->linkByHrefExists('/fi/kittens/delete', 0, 'Local task link path that is subpath for an alias lead to correct URL when language prefix exists..');
  }

  /**
   * Ensures that non-existing paths are returning 404 page.
   */
  public function testNonExistingPath() {
    $this->drupalGet('/kittens/are-faken');
    $this->assertSession()->statusCodeEquals(404);
  }

}
