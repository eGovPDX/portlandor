<?php

namespace Drupal\Tests\ds\Functional;

/**
 * Tests for cache tags associated with an entity.
 *
 * @group ds
 */
class CacheTagsTest extends FastTestBase {

  /**
   * Tests setting the correct cache tags.
   */
  public function testUserCacheTags() {
    // Create a node.
    $settings = ['type' => 'article', 'promote' => 1];
    $node = $this->drupalCreateNode($settings);

    // Create field CSS classes.
    $edit = ['fields' => "test_field_class\ntest_field_class_2|Field class 2"];
    $this->drupalPostForm('admin/structure/ds/classes', $edit, t('Save configuration'));

    // Create a token field.
    $token_field = [
      'name' => 'Token field',
      'id' => 'token_field',
      'entities[node]' => '1',
      'content[value]' => '[node:title]',
    ];
    $this->dsCreateTokenField($token_field);

    // Select layout.
    $this->dsSelectLayout();

    // Configure fields.
    $fields = [
      'fields[dynamic_token_field:node-token_field][region]' => 'header',
      'fields[body][region]' => 'right',
      'fields[node_link][region]' => 'footer',
      'fields[body][label]' => 'above',
      'fields[node_submitted_by][region]' => 'header',
    ];
    $this->dsConfigureUi($fields);

    $this->drupalGet('node/' . $node->id());
    $headers = $this->drupalGetHeader('X-Drupal-Cache-Tags');
    $this->assertTrue(
      strpos($headers, 'user:' . $node->getRevisionUser()->getOriginalId()),
      'User cache tag found'
    );
  }

}
