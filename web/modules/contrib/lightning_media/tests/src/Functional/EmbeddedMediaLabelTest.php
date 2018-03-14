<?php

namespace Drupal\Tests\lightning_media\Functional;

use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * @group lightning
 * @group lightning_media
 */
class EmbeddedMediaLabelTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lightning_media_twitter',
    'node',
  ];

  /**
   * ID of the content type created for this test.
   *
   * @var string
   */
  protected $nodeType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->nodeType = $this->drupalCreateContentType()->id();
  }

  /**
   * Tests that the label of an embedded media item does not appear in the
   * rendered host entity.
   */
  public function testEmbeddedMediaItem() {
    $media = Media::create([
      'bundle' => 'tweet',
      'name' => 'Here be dragons',
      'embed_code' => 'https://twitter.com/50NerdsofGrey/status/757319527151636480',
    ]);
    $media->setPublished();
    $media->save();

    $node = Node::create([
      'type' => $this->nodeType,
      'body' => '<drupal-entity data-embed-button="media_browser" data-entity-embed-display="view_mode:media.embedded" data-entity-type="media" data-entity-uuid="' . $media->uuid() . '"></drupal-entity>',
      'title' => $this->getRandomGenerator()->word(16),
    ]);
    $node->setPublished();
    $node->save();

    $this->drupalGet($node->toUrl());
    $this->assertSession()->pageTextNotContains($media->label());
  }

}
