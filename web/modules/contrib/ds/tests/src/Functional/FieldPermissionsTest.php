<?php

namespace Drupal\Tests\ds\Functional;

/**
 * Tests for testing field permissions.
 *
 * @group ds
 */
class FieldPermissionsTest extends FastTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'field_ui',
    'taxonomy',
    'block',
    'ds',
    'ds_extras',
    'ds_test',
    'views',
    'views_ui',
  ];

  /**
   * Tests field permissions.
   */
  public function testFieldPermissions() {

    $fields = [
      'fields[body][region]' => 'right',
      'fields[test_field][region]' => 'left',
    ];

    $this->config('ds_extras.settings')->set('field_permissions', TRUE)->save();
    \Drupal::moduleHandler()->resetImplementations();

    $this->dsSelectLayout();
    $this->dsConfigureUi($fields);

    // Create a node.
    $settings = ['type' => 'article'];
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('group-right');
    $this->assertSession()->pageTextNotContains('Test field plugin on node ' . $node->id());

    // Give permissions.
    $edit = [
      'authenticated[view node_author on node]' => 1,
      'authenticated[view test_field on node]' => 1,
    ];
    $this->drupalPostForm('admin/people/permissions', $edit,'Save permissions');
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('group-left');
    $this->assertSession()->pageTextContains('Test field plugin on node ' . $node->id());
  }

}
