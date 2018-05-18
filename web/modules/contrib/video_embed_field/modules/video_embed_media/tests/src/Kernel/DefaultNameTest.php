<?php

namespace Drupal\Tests\video_embed_media\Kernel;

use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\Tests\media\Kernel\MediaKernelTestBase;
use Drupal\video_embed_media\Plugin\media\Source\VideoEmbedField;

/**
 * Test the media bundle default names.
 *
 * @group video_embed_media
 */
class DefaultNameTest extends MediaKernelTestBase {

  /**
   * The plugin under test.
   *
   * @var \Drupal\video_embed_media\Plugin\media\Source\VideoEmbedField
   */
  protected $plugin;

  /**
   * The created media type.
   *
   * @var \Drupal\media\Entity\MediaType;
   */
  protected $entityType;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'video_embed_field',
    'video_embed_media',
    'media',
    'file',
    'views',
  ];

  /**
   * Test cases for ::testDefaultName().
   */
  public function defaultNameTestCases() {
    return [
      'YouTube' => [
        'https://www.youtube.com/watch?v=gnERPdAiuSo',
        'YouTube Video (gnERPdAiuSo)',
      ],
      'Vimeo' => [
        'https://vimeo.com/21681203',
        'Drupal Commerce at DrupalCon Chicago',
      ],
    ];
  }

  /**
   * Test the default name.
   *
   * @dataProvider defaultNameTestCases
   */
  public function testDefaultName($input, $expected) {
    $field_name = $this->plugin->getSourceFieldDefinition($this->entityType)->getName();
    $entity = Media::create([
      'bundle' => $this->entityType->id(),
      $field_name => [['value' => $input]],
    ]);
    $actual = $this->plugin->getMetadata($entity, 'default_name');
    $this->assertEquals($expected, $actual);
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig(['video_embed_field', 'video_embed_media']);

    $this->entityType = $this->createMediaType('video_embed_field');

    $this->plugin = $this->entityType->getSource();
  }

}
