<?php

namespace Drupal\Tests\media_embed_field\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\media_embed_field\Functional\EntityDisplaySetupTrait;

/**
 * Test the colorbox formatter.
 *
 * @group media_embed_field
 */
class ColorboxFormatterTest extends WebDriverTestBase {

  use EntityDisplaySetupTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'colorbox',
    'media_embed_field',
    'colorbox_library_test',
    'media_embed_field_mock_provider',
  ];

  /**
   * How long it takes for colorbox to open.
   */
  const COLORBOX_LAUNCH_TIME = 250;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setupEntityDisplays();
  }

  /**
   * Test the colorbox formatter.
   */
  public function testColorboxFormatter() {
    $this->setDisplayComponentSettings('media_embed_field_colorbox', [
      'autoplay' => FALSE,
      'responsive' => TRUE,
    ]);
    $node = $this->createMediaNode('https://example.com/mock_media');
    $this->drupalGet('node/' . $node->id());
    $this->click('.media-embed-field-launch-modal');
    $this->getSession()->wait(static::COLORBOX_LAUNCH_TIME);
    $this->assertSession()->elementExists('css', '#colorbox .media-embed-field-responsive-media');
    // Make sure the right library files are loaded on the page.
    $this->assertSession()->elementExists('css', 'link[href*="colorbox_style.css"]');
    $this->assertSession()->elementExists('css', 'link[href*="media_embed_field.responsive-media.css"]');
  }

}
