<?php

namespace Drupal\Tests\media_embed_media\Kernel;

use Drupal\Core\File\FileSystemInterface;
use Drupal\media\Entity\Media;
use Drupal\Tests\media\Kernel\MediaKernelTestBase;

/**
 * Test the provided fields.
 *
 * @group media_embed_media
 */
class ProvidedFieldsTest extends MediaKernelTestBase {

  /**
   * The plugin under test.
   *
   * @var \Drupal\media_embed_media\Plugin\media\Source\MediaEmbedField
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
    'media_embed_media',
    'media_embed_field',
  ];

  /**
   * Test cases for ::testProvidedFields().
   */
  public function providedFieldsTestCases() {
    return [
      'Media ID (YouTube)' => [
        'https://www.youtube.com/watch?v=gnERPdAiuSo',
        'id',
        'gnERPdAiuSo',
      ],
      'Media ID (Vimeo)' => [
        'https://vimeo.com/channels/staffpicks/153786080',
        'id',
        '153786080',
      ],
      'Media Source (YouTube)' => [
        'https://www.youtube.com/watch?v=gnERPdAiuSo',
        'source',
        'youtube',
      ],
      'Media Source (Vimeo)' => [
        'https://vimeo.com/channels/staffpicks/159700995',
        'source',
        'vimeo',
      ],
      'Media Thumbnail (YouTube)' => [
        'https://www.youtube.com/watch?v=gnERPdAiuSo',
        'image_local_uri',
        'public://media_thumbnails/gnERPdAiuSo.jpg',
      ],
      'Media Thumbnail (Vimeo)' => [
        'https://vimeo.com/channels/staffpicks/153786080',
        'image_local_uri',
        'public://media_thumbnails/153786080.jpg',
      ],
    ];
  }

  /**
   * Test the default thumbnail.
   */
  public function testDefaultThumbnail() {
    $source_field = $this->plugin->getSourceFieldDefinition($this->entityType);
    $field_name = $source_field->getName();
    $entity = Media::create([
      'bundle' => $this->entityType->id(),
      $field_name => [['value' => 'https://vimeo.com/channels/staffpicks/153786080-fake-url']],
    ]);
    $this->assertEquals('public://media-icons/generic/media.png', $this->plugin->getMetadata($entity, 'thumbnail_uri'));
  }

  /**
   * Test the fields provided by the integration.
   *
   * @dataProvider providedFieldsTestCases
   */
  public function testProvidedFields($input, $field, $expected) {
    $source_field = $this->plugin->getSourceFieldDefinition($this->entityType);
    $field_name = $source_field->getName();
    $entity = Media::create([
      'bundle' => $this->entityType->id(),
      $field_name => [['value' => $input]],
    ]);

    // The 'image_local_url' returns path to the local image only if it actually
    // exists. Otherwise the default image is returned.
    if ($field == 'image_local_uri') {
      touch($expected);
    }

    $actual = $this->plugin->getMetadata($entity, $field);
    $this->assertEquals($expected, $actual);
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->entityType = $this->createMediaType('media_embed_field');

    $this->plugin = $this->entityType->getSource();

    $dir = 'public://media_thumbnails';
    $this->container->get('file_system')->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
  }

}
