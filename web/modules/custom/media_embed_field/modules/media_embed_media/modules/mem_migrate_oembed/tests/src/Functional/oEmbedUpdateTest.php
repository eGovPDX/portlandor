<?php

namespace Drupal\Tests\mem_embed_media\Functional;

use Drupal\media\Entity\MediaType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;

/**
 * Tests the MEM to OEmbed migration.
 *
 * @group mem_embed_media
 */
class oEmbedUpdateTest extends BrowserTestBase {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['mem_migrate_oembed'];

  /**
   * Tests the MEM to OEmbed migration.
   */
  public function testOEmbedUpdate() {

    $mediaType = $this->createMediaType('media_embed_field');
    $this->assertEqual($mediaType->getSource()->getPluginId(), 'media_embed_field');

    $sourceField = $mediaType->getSource()->getSourceFieldDefinition($mediaType);
    $this->assertEqual($sourceField->getType(), 'media_embed_field');

    $formDisplay = entity_get_form_display('media', $mediaType->id(), 'default');
    $formField = $formDisplay->getComponent($sourceField->getName());

    $this->assertEqual($formField['type'], 'media_embed_field_textfield');

    /** @var \Drupal\mem_migrate_oembed\MemMigrate $memService */
    $memService = \Drupal::service('mem_migrate_oembed.migrate');
    $memService->migrate();

    /** @var \Drupal\media\Entity\MediaType $mediaType */
    $mediaType = MediaType::load($mediaType->id());
    $this->assertEqual($mediaType->getSource()->getPluginId(), 'oembed:media');

    $sourceField = $mediaType->getSource()->getSourceFieldDefinition($mediaType);
    $this->assertEqual($sourceField->getType(), 'string');

    $formDisplay = entity_get_form_display('media', $mediaType->id(), 'default');
    $formField = $formDisplay->getComponent($sourceField->getName());

    $this->assertEqual($formField['type'], 'oembed_textfield');
  }

}
