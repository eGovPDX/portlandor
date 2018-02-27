<?php

namespace Drupal\image_widget_crop_examples\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests image_widget_crop_examples.
 *
 * @group image_widget_crop_examples
 *
 * @ingroup media
 */
class ImageWidgetCropExamplesTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'menu_ui',
    'path',
    'media',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Theme needs to be set before enabling image_widget_crop_examples because
    // of dependency.
    \Drupal::service('theme_handler')->install(['bartik']);
    $this->config('system.theme')
      ->set('default', 'bartik')
      ->save();
    $this->assertTrue(\Drupal::service('module_installer')->install(['image_widget_crop_examples']), 'image_widget_crop_examples installed.');
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests if image_widget_crop_example is correctly installed.
   */
  public function testInstalled() {
    $this->drupalGet('');
    $this->assertTitle('Image Widget Crop examples | Drupal');
    $this->assertText('Image Widget Crop examples');
    $this->assertText('Welcome to Image Widget Crop example.');
    $this->assertText('Image Widget Crop provides an interface for using the features of the Crop API.');
    $this->assertText('You can test the functionality with custom content types created for the demonstration of features from Image Widget Crop:');
  }

}
