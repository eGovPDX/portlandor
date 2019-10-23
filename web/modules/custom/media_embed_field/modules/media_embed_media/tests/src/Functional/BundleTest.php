<?php

namespace Drupal\Tests\media_embed_media\Functional;

use Drupal\Tests\media\Functional\MediaFunctionalTestBase;
use Drupal\Tests\media_embed_field\Functional\AdminUserTrait;

/**
 * Test the media_embed_field media integration.
 *
 * @group media_embed_media
 */
class BundleTest extends MediaFunctionalTestBase {

  use AdminUserTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'media_embed_field',
    'media_embed_media',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test the dialog form.
   */
  public function testMediaBundleCreation() {
    $this->drupalLogin($this->adminUser);

    // Create bundle and modify form display.
    $media_type = $this->createMediaType('media_embed_field', ['bundle' => 'media_bundle']);
    $source = $media_type->getSource();
    $source_field = $source->getSourceFieldDefinition($media_type);
    if ($source_field->isDisplayConfigurable('form')) {
      // Use the default widget and settings.
      $component = \Drupal::service('plugin.manager.field.widget')
        ->prepareConfiguration('media_embed_field', []);

      // @todo Replace entity_get_form_display() when #2367933 is done.
      // https://www.drupal.org/node/2872159.
      $this->container->get('entity_display.repository')->getFormDisplay('media', $media_type->id(), 'default')
        ->setComponent($source_field->getName(), $component)
        ->save();
    }

    // Ensure the media field is added to the media entity.
    $this->drupalGet('admin/structure/media/manage/media_bundle/fields');
    $this->assertSession()->pageTextContains('field_media_media_embed_field');
    $this->assertSession()->pageTextContains('Media URL');

    // Add a media entity with the new field.
    $this->drupalGet('media/add/media_bundle');
    $this->submitForm([
      'name[0][value]' => 'Drupal media!',
      'field_media_media_embed_field[0][value]' => 'https://www.youtube.com/watch?v=XgYu7-DQjDQ',
    ], 'Save');
    // We should see the media thumbnail on the media page.
    $this->assertContains('media_thumbnails/XgYu7-DQjDQ.jpg', $this->getSession()->getPage()->getHtml());
  }

}
