<?php

namespace Drupal\Tests\lightning_media\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\media\Functional\MediaFunctionalTestCreateMediaTypeTrait;

/**
 * @group lightning
 * @group lightning_media
 */
class MediaBrowserTest extends BrowserTestBase {

  use MediaFunctionalTestCreateMediaTypeTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'field_ui',
    'lightning_media',
    'node',
  ];

  /**
   * The ID of the content type created for the test.
   *
   * @var string
   */
  protected $nodeType;

  /**
   * The ID of the media type created for the test.
   *
   * @var string
   */
  protected $mediaType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');
    $this->nodeType = $this->drupalCreateContentType()->id();
    $this->mediaType = $this->createMediaType([], 'image')->id();
  }

  /**
   * Tests that the media browser is the default widget for a new media
   * reference field.
   */
  public function testNewMediaReferenceField() {
    $account = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($account);

    $this->drupalGet("/admin/structure/types/manage/$this->nodeType/fields");
    $this->clickLink('Add field');
    $values = [
      'new_storage_type' => 'field_ui:entity_reference:media',
      'label' => 'Foobar',
      'field_name' => 'foobar',
    ];
    $this->drupalPostForm(NULL, $values, 'Save and continue');
    $this->drupalPostForm(NULL, [], 'Save field settings');
    $values = [
      "settings[handler_settings][target_bundles][$this->mediaType]" => $this->mediaType,
    ];
    $this->drupalPostForm(NULL, $values, 'Save settings');

    $component = entity_get_form_display('node', $this->nodeType, 'default')
      ->getComponent('field_foobar');

    $this->assertInternalType('array', $component);
    $this->assertSame('entity_browser_entity_reference', $component['type']);
    $this->assertSame('media_browser', $component['settings']['entity_browser']);
  }

}
