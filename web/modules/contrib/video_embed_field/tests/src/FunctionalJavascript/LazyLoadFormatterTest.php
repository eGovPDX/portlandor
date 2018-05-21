<?php

namespace Drupal\Tests\video_embed_field\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\video_embed_field\Functional\EntityDisplaySetupTrait;

/**
 * Test the lazy load formatter.
 *
 * @group video_embed_field
 */
class LazyLoadFormatterTest extends JavascriptTestBase {

  use EntityDisplaySetupTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'video_embed_field',
    'video_embed_field_mock_provider',
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
    $this->setDisplayComponentSettings('video_embed_field_lazyload', [
      'autoplay' => TRUE,
      'responsive' => TRUE,
    ]);
    $node = $this->createVideoNode('https://example.com/mock_video');
    $this->drupalGet('node/' . $node->id());
    $this->click('.video-embed-field-lazy');
    $this->assertSession()->elementExists('css', '.video-embed-field-lazy .video-embed-field-responsive-video');
    // Make sure the right library files are loaded on the page.
    $this->assertSession()->elementContains('css', 'style', 'video_embed_field/css/video_embed_field.responsive-video.css');
  }

}
