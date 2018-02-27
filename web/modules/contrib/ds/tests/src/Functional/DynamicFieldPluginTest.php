<?php

namespace Drupal\Tests\ds\Functional;

/**
 * Tests for managing custom code, and block fields.
 *
 * @group ds
 */
class DynamicFieldPluginTest extends FastTestBase {

  /**
   * Test Display fields.
   */
  public function testDsFields() {

    $edit = [
      'name' => 'Test field',
      'id' => 'test_field',
      'entities[node]' => '1',
      'content[value]' => 'Test field',
    ];

    $this->dsCreateTokenField($edit);

    // Create the same and assert it already exists.
    $this->drupalPostForm('admin/structure/ds/fields/manage_token', $edit, 'Save');
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');

    $this->dsSelectLayout();

    // Assert it's found on the Field UI for article.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertSession()->responseContains('fields[dynamic_token_field:node-test_field][weight]');

    // Assert it's not found on the Field UI for users.
    $this->drupalGet('admin/config/people/accounts/display');
    $this->assertSession()->responseNotContains('fields[dynamic_token_field:node-test_field][weight]');

    // Update testing label.
    $edit = [
      'name' => 'Test field 2',
    ];
    $this->drupalPostForm('admin/structure/ds/fields/manage_token/test_field', $edit,'Save');
    $this->assertSession()->pageTextContains('The field Test field 2 has been saved');

    // Use the Field UI limit option.
    $this->dsSelectLayout([], [], 'admin/structure/types/manage/page/display');
    $this->dsSelectLayout([], [], 'admin/structure/types/manage/article/display/teaser');
    $edit = [
      'ui_limit' => 'article|default',
    ];
    $this->drupalPostForm('admin/structure/ds/fields/manage_token/test_field', $edit,'Save');

    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertSession()->responseContains('fields[dynamic_token_field:node-test_field][weight]');

    $this->drupalGet('admin/structure/types/manage/article/display/teaser');
    $this->assertSession()->responseNotContains('fields[dynamic_token_field:node-test_field][weight]');
    $this->drupalGet('admin/structure/types/manage/page/display');
    $this->assertSession()->responseNotContains('fields[dynamic_token_field:node-test_field][weight]');
    $edit = [
      'ui_limit' => 'article|*',
    ];
    $this->drupalPostForm('admin/structure/ds/fields/manage_token/test_field', $edit,'Save');
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertSession()->responseContains('fields[dynamic_token_field:node-test_field][weight]');
    $this->drupalGet('admin/structure/types/manage/article/display/teaser');
    $this->assertSession()->responseContains('fields[dynamic_token_field:node-test_field][weight]');

    // Remove the field.
    $this->drupalPostForm('admin/structure/ds/fields/delete/test_field', [],'Confirm');
    $this->assertSession()->pageTextContains('The field Test field 2 has been deleted');

    // Assert the field is gone at the manage display screen.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertSession()->responseNotContains('fields[dynamic_token_field:node-test_field][weight]');

    // Block fields.
    $edit = [
      'name' => 'Test block field',
      'id' => 'test_block_field',
      'entities[node]' => '1',
      'block' => 'system_powered_by_block',
    ];

    $this->dsCreateBlockField($edit);

    // Create the same and assert it already exists.
    $this->drupalPostForm('admin/structure/ds/fields/manage_block', $edit,'Save');
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');

    $this->dsSelectLayout();

    // Assert it's found on the Field UI for article.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertSession()->responseContains('fields[dynamic_block_field:node-test_block_field][weight]');

    // Assert it's not found on the Field UI for users.
    $this->drupalGet('admin/config/people/accounts/display');
    $this->assertSession()->responseNotContains('fields[dynamic_block_field:node-test_block_field][weight]');

    // Update testing label.
    $edit = [
      'name' => 'Test block field 2',
    ];
    $this->drupalPostForm('admin/structure/ds/fields/manage_block/test_block_field', $edit,'Save');
    $this->assertSession()->pageTextContains('The field Test block field 2 has been saved');

    // Remove the block field.
    $this->drupalPostForm('admin/structure/ds/fields/delete/test_block_field', [],'Confirm');
    $this->assertSession()->pageTextContains('The field Test block field 2 has been deleted');

    // Assert the block field is gone at the manage display screen.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertSession()->responseNotContains('fields[dynamic_block_field:node-test_block_field][weight]');

    // Create a configurable block field.
    $edit = [
      'name' => 'Configurable block <script>alert("XSS")</script>',
      'id' => 'test_block_configurable',
      'entities[node]' => '1',
      'block' => 'system_menu_block:tools',
    ];

    $this->dsCreateBlockField($edit);

    // Try to set the depth to 3, to ensure we can save the block.
    $edit = [
      'depth' => '3',
    ];
    $this->drupalPostForm('admin/structure/ds/fields/manage_block/test_block_configurable/block_config', $edit,'Save');

    // Assert it's found on the Field UI for article.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertSession()->responseContains('fields[dynamic_block_field:node-test_block_configurable][weight]');

    // Assert it's not found on the Field UI for users.
    $this->drupalGet('admin/config/people/accounts/display');
    $this->assertSession()->responseNotContains('fields[dynamic_block_field:node-test_block_configurable][weight]');

    // Add block to display.
    $fields = [
      'fields[dynamic_block_field:node-test_block_configurable][region]' => 'left',
      'fields[dynamic_block_field:node-test_block_configurable][label]' => 'above',
    ];
    $this->dsConfigureUi($fields, 'admin/structure/types/manage/article/display');

    /* @var \Drupal\node\NodeInterface $node */
    $node = $this->entitiesTestSetup();

    // Look at node and verify the menu is visible.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('Add content');

    // Ensure that there is no XSS attack possible
    $this->assertSession()->responseNotContains('<script>alert("XSS")</script>');

    // Try to set the depth to 3, to ensure we can save the block.
    $edit = [
      'level' => '2',
    ];
    $this->drupalPostForm('admin/structure/ds/fields/manage_block/test_block_configurable/block_config', $edit,'Save');

    // Look at node and verify the menu is not visible.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseNotContains('Add content');
  }

}
