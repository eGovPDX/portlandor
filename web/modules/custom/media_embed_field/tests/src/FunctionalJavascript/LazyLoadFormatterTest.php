<?php

namespace Drupal\Tests\media_embed_field\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\media_embed_field\Functional\EntityDisplaySetupTrait;

/**
 * Test the lazy load formatter.
 *
 * @group media_embed_field
 */
class LazyLoadFormatterTest extends WebDriverTestBase {

  use EntityDisplaySetupTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'media_embed_field',
    'media_embed_field_mock_provider',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setupEntityDisplays();
  }

  /**
   * Test the lazy load formatter.
   */
  public function testColorboxFormatter() {
    $this->setDisplayComponentSettings('media_embed_field_lazyload', [
      'autoplay' => TRUE,
      'responsive' => TRUE,
    ]);
    $node = $this->createMediaNode('https://example.com/mock_media');
    $this->drupalGet('node/' . $node->id());
    $this->click('.media-embed-field-lazy');
    $this->assertSession()->elementExists('css', '.media-embed-field-lazy .media-embed-field-responsive-media');
    // Make sure the right library files are loaded on the page.
    $this->assertSession()->elementExists('css', 'link[href*="media_embed_field.responsive-media.css"]');
  }

}
