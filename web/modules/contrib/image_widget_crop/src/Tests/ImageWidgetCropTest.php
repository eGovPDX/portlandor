<?php

namespace Drupal\image_widget_crop\Tests;

use Drupal\crop\Entity\CropType;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;

/**
 * Minimal test case for the image_widget_crop module.
 *
 * @group image_widget_crop
 *
 * @ingroup media
 */
class ImageWidgetCropTest extends WebTestBase {

  /**
   * User with permissions to create content.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'crop',
    'image',
    'image_widget_crop',
  ];

  /**
   * Prepares environment for the tests.
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['name' => 'Crop test', 'type' => 'crop_test']);

    $this->user = $this->createUser([
      'access content overview',
      'administer content types',
      'edit any crop_test content',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Test Image Widget Crop UI.
   */
  public function testCropUi() {
    // Test that when a crop has more than one usage we have a warning.
    $this->createImageField('field_image_crop_test', 'crop_test', 'image_widget_crop', [], [], ['crop_list' => ['crop_16_9' => 'crop_16_9']]);
    $this->drupalGetTestFiles('image');

    $this->drupalGet('node/add/crop_test');
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'files[field_image_crop_test_0]' => \Drupal::service('file_system')->realpath('public://image-test.jpg'),
    ];
    $this->drupalPostAjaxForm(NULL, $edit, $this->getButtonName('//input[@type="submit" and @value="Upload" and @data-drupal-selector="edit-field-image-crop-test-0-upload-button"]'));

    $node = Node::create([
      'title' => '2nd node using it',
      'type' => 'crop_test',
      'field_image_crop_test' => 1,
      'alt' => $this->randomMachineName(),
    ]);
    $node->save();

    /** @var \Drupal\file\FileUsage\FileUsageInterface $usage */
    $usage = \Drupal::service('file.usage');
    $usage->add(\Drupal::service('entity_type.manager')->getStorage('file')->load(1), 'image_widget_crop', 'node', $node->id());

    $this->drupalGet('node/1/edit');

    $this->assertRaw(t('This crop definition affects more usages of this image'));

  }

  /**
   * Test Image Widget Crop.
   */
  public function testImageWidgetCrop() {
    // Test that crop widget works properly.
    $this->createImageField('field_image_crop_test', 'crop_test', 'image_widget_crop', [], [], ['crop_list' => ['crop_16_9' => 'crop_16_9']]);
    $this->drupalGetTestFiles('image');

    $this->drupalGet('node/add/crop_test');

    // Assert that there is no crop widget, neither 'Alternative text' text
    // filed nor 'Remove' button yet.
    $raw = '<summary role="button" aria-controls="edit-field-image-crop-test-0-image-crop-crop-wrapper" aria-expanded="false" aria-pressed="false">Crop image</summary>';
    $this->assertNoRaw($raw);
    $this->assertNoText('Alternative text');
    $this->assertNoFieldByName('field_image_crop_test_0_remove_button');

    $image = [];
    // Upload an image in field_image_crop_test_0.
    $image['files[field_image_crop_test_0]'] = $this->container->get('file_system')->realpath('public://image-test.jpg');
    $this->drupalPostAjaxForm(NULL, $image, $this->getButtonName('//input[@type="submit" and @value="Upload" and @data-drupal-selector="edit-field-image-crop-test-0-upload-button"]'));

    // Assert that now crop widget and 'Alternative text' text field appear and
    // that 'Remove' button exists.
    $this->assertRaw($raw);
    $this->assertText('Alternative text');
    $this->assertFieldByName('field_image_crop_test_0_remove_button');

    // Set title and 'Alternative text' text field and save.
    $title = $this->randomMachineName();
    $edit = [
      'title[0][value]' => $title,
      'field_image_crop_test[0][alt]' => $this->randomMachineName(),
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('Crop test ' . $title . ' has been created.');
    $url = $this->getUrl();
    $nid = substr($url, -1, strrpos($url, '/'));

    // Edit crop image.
    $this->drupalGet('node/' . $nid . '/edit');

    // Verify that the 'Remove' button works properly.
    $this->assertText('Alternative text');
    $this->drupalPostForm(NULL, NULL, 'Remove');
    $this->assertNoText('Alternative text');

    // Re-upload the image and set the 'Alternative text'.
    $this->drupalPostAjaxForm(NULL, $image, $this->getButtonName('//input[@type="submit" and @value="Upload" and @data-drupal-selector="edit-field-image-crop-test-0-upload-button"]'));

    // Verify that the 'Preview' button works properly.
    $this->drupalPostForm(NULL, $edit, 'Preview');
    $this->assertLink('Back to content editing');
    $this->clickLink('Back to content editing');

    // Verify that there is an image style preview.
    $this->assertFieldByName('field_image_crop_test[0][width]', '40');
    $this->assertFieldByName('field_image_crop_test[0][height]', '20');

  }

  /**
   * Gets IEF button name.
   *
   * @param string $xpath
   *   Xpath of the button.
   *
   * @return string
   *   The name of the button.
   */
  protected function getButtonName($xpath) {
    $retval = '';

    /** @var \SimpleXMLElement[] $elements */
    if ($elements = $this->xpath($xpath)) {
      foreach ($elements[0]->attributes() as $name => $value) {
        if ($name == 'name') {
          $retval = (string) $value;
          break;
        }
      }
    }
    return $retval;
  }

  /**
   * Create a new image field.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $type_name
   *   The node type that this field will be added to.
   * @param string $widget_name
   *   The name of the widget.
   * @param array $storage_settings
   *   A list of field storage settings that will be added to the defaults.
   * @param array $field_settings
   *   A list of instance settings that will be added to the instance defaults.
   * @param array $widget_settings
   *   A list of widget settings that will be added to the widget defaults.
   */
  protected function createImageField($name, $type_name, $widget_name, array $storage_settings = [], array $field_settings = [], array $widget_settings = []) {
    \Drupal::entityTypeManager()->getStorage('field_storage_config')->create([
      'field_name' => $name,
      'entity_type' => 'node',
      'type' => 'image',
      'settings' => $storage_settings,
      'cardinality' => !empty($storage_settings['cardinality']) ? $storage_settings['cardinality'] : 1,
    ])->save();

    $field_config = \Drupal::entityTypeManager()->getStorage('field_config')->create([
      'field_name' => $name,
      'label' => $name,
      'entity_type' => 'node',
      'bundle' => $type_name,
      'required' => !empty($field_settings['required']),
      'settings' => $field_settings,
    ]);
    $field_config->save();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.' . $type_name . '.default');
    $form_display->setComponent($name, [
      'type' => $widget_name,
      'settings' => $widget_settings,
    ])->save();

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display */
    $view_display = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load('node.' . $type_name . '.default');
    $view_display->setComponent($name)
      ->save();

  }

}
