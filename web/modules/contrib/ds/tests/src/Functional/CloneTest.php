<?php

namespace Drupal\Tests\ds\Functional;

/**
 * Tests for the manage display tab in Display Suite.
 *
 * @group ds
 */
class CloneTest extends FastTestBase {

  use DsTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'user',
    'comment',
    'field_ui',
    'ds',
  ];

  /**
   * Test adding a cloning a layout.
   */
  public function testClone() {
    // Go to the teaser display mode and select a DS layout.
    $this->dsSelectLayout([], [], 'admin/structure/types/manage/article/display/teaser');
    $this->assertSession()->pageTextContains('Two column stacked layout');

    // Go back to the default view mode.
    $this->drupalGet('admin/structure/types/manage/article/display');

    // Clone layout, this will clone from the teaser view mode.
    $page = $this->getSession()->getPage();
    $button = $page->findById('edit-clone-submit');
    $button->click();

    // Check for message.
    $this->assertSession()->pageTextContains('The layout has been cloned.');

    // Check that this now also has the expected region layout.
    $option_field = $this->assertSession()->optionExists('edit-layout', 'ds_2col_stacked');
    $this->assertTrue($option_field->hasAttribute('selected'));
  }

}
