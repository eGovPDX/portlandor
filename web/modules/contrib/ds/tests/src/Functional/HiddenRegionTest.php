<?php

namespace Drupal\Tests\ds\Functional;

/**
 * Tests for testing the hidden region option.
 *
 * @group ds
 */
class HiddenRegionTest extends FastTestBase {

  /**
   * Tests hidden region functionality.
   */
  public function testHiddenRegion() {
    // Enable the hidden region option.
    $edit = [
      'fs3[hidden_region]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/ds/settings', $edit, t('Save configuration'));

    $this->dsSelectLayout();

    // Create a node.
    $settings = ['type' => 'article'];
    $node = $this->drupalCreateNode($settings);

    // Configure fields.
    $fields = [
      'fields[body][region]' => 'right',
      'fields[test_field][region]' => 'ds_hidden',
    ];
    $this->dsConfigureUi($fields);

    // Test field not printed.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextNotContains('Test field plugin on node ' . $node->id());

    // Configure fields.
    $fields = [
      'fields[body][region]' => 'right',
      'fields[test_field][region]' => 'right',
    ];
    $this->dsConfigureUi($fields);

    // Test field printed.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains('Test field plugin on node ' . $node->id());
  }

}
