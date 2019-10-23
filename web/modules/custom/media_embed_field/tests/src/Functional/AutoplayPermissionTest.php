<?php

namespace Drupal\Tests\media_embed_field\Functional;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the autoplay permission works.
 *
 * @group media_embed_field
 */
class AutoplayPermissionTest extends BrowserTestBase {

  use EntityDisplaySetupTrait;

  public static $modules = [
    'media_embed_field',
    'node',
  ];

  /**
   * Test the autoplay permission works.
   */
  public function testAutoplay() {
    $this->setupEntityDisplays();
    $node = $this->createMediaNode('https://vimeo.com/80896303');
    $this->setDisplayComponentSettings('media_embed_field_media', [
      'autoplay' => TRUE,
    ]);
    $bypass_autoplay_user = $this->drupalCreateUser(['never autoplay media videos']);
    // Assert a user with the permission doesn't get autoplay.
    $this->drupalLogin($bypass_autoplay_user);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->elementAttributeContains('css', 'iframe', 'src', 'autoplay=0');
    // Ensure an anonymous user gets autoplay.
    $this->drupalLogout();
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->elementAttributeContains('css', 'iframe', 'src', 'autoplay=1');
  }

}
