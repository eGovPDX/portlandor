<?php

namespace Drupal\Tests\ds\Functional;

/**
 * Tests DS field plugins.
 *
 * @group ds
 */
class FieldPluginTest extends FastTestBase {

  /**
   * Test basic Display Suite fields plugins.
   */
  public function testFieldPlugin() {
    // Rename the title field.
    $edit = [
      'title_label' => 'alternative article title',
    ];
    $this->drupalPostForm('admin/structure/types/manage/article', $edit, t('Save content type'));

    $this->dsSelectLayout();

    // Find the two field plugins from the test module on the node type.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertSession()->pageTextContains('Test field plugin');
    // One is altered by hook_ds_fields_info_alter()
    $this->assertSession()->pageTextContains('Field altered');

    $empty = [];
    $edit = ['layout' => 'ds_2col_stacked'];
    $this->dsSelectLayout($edit, $empty, 'admin/config/people/accounts/display');

    // Fields can not be found on user.
    $this->drupalGet('admin/config/people/accounts/display');
    $this->assertSession()->pageTextNotContains('Test code field from plugin');
    $this->assertSession()->pageTextNotContains('Field altered');

    // Select layout.
    $this->dsSelectLayout();

    $fields = [
      'fields[node_title][region]' => 'right',
      'fields[node_title][label]' => 'inline',
      'fields[node_author][region]' => 'left',
      'fields[node_links][region]' => 'left',
      'fields[body][region]' => 'right',
      'fields[test_field][region]' => 'right',
      'fields[test_multiple_field][region]' => 'right',
      'fields[test_field_empty_string][region]' => 'right',
      'fields[test_field_empty_string][label]' => 'inline',
      'fields[test_field_false][region]' => 'right',
      'fields[test_field_false][label]' => 'inline',
      'fields[test_field_null][region]' => 'right',
      'fields[test_field_null][label]' => 'inline',
      'fields[test_field_nothing][region]' => 'right',
      'fields[test_field_nothing][label]' => 'inline',
      'fields[test_field_zero_int][region]' => 'right',
      'fields[test_field_zero_int][label]' => 'inline',
      'fields[test_field_zero_string][region]' => 'right',
      'fields[test_field_zero_string][label]' => 'inline',
      'fields[test_field_zero_float][region]' => 'right',
      'fields[test_field_zero_float][label]' => 'inline',
      'fields[test_multiple_entity_test_field][region]' => 'right',
      'fields[test_multiple_entity_test_field][label]' => 'inline',
    ];

    $this->dsSelectLayout();
    $this->dsConfigureUi($fields);

    // Create a node.
    $settings = ['type' => 'article'];
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());

    $this->assertSession()->responseContains('group-left');
    $this->assertSession()->responseContains('group-right');
    $this->assertSession()->pageTextContains('Test field plugin on node ' . $node->id());
    $this->assertSession()->pageTextContains('Test row one of multiple field plugin on node ' . $node->id());
    $this->assertSession()->pageTextContains('Test row two of multiple field plugin on node ' . $node->id());
    $this->assertSession()->pageTextContains('Test field plugin that returns an empty string');
    $this->assertSession()->pageTextNotContains('Test field plugin that returns FALSE');
    $this->assertSession()->pageTextNotContains('Test field plugin that returns NULL');
    $this->assertSession()->pageTextNotContains('Test field plugin that returns nothing');
    $this->assertSession()->pageTextNotContains('Test field plugin that returns an empty array');
    $this->assertSession()->pageTextContains('Test field plugin that returns zero as an integer');
    $this->assertSession()->pageTextContains('Test field plugin that returns zero as a string');
    $this->assertSession()->pageTextContains('Test field plugin that returns zero as a floating point number');
    $this->assertSession()->pageTextContains('alternative article title');
    $this->assertSession()->pageTextContains('Multiple entity test field plugin');

    // Check if the multiple entity test field appears on user entities
    $this->dsSelectLayout([], [],'admin/config/people/accounts/display');
    $fields = [
      'fields[test_multiple_entity_test_field][region]' => 'right',
      'fields[test_multiple_entity_test_field][label]' => 'inline',
    ];

    $this->dsConfigureUi($fields,'admin/config/people/accounts/display');
    $this->drupalGet('user/' . $this->adminUser->id());

    $this->assertSession()->pageTextContains('Multiple entity test field plugin');
  }

}
