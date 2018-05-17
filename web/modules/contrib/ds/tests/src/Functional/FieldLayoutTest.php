<?php

namespace Drupal\Tests\ds\Functional;

/**
 * Tests compatibility with field layout
 *
 * @group ds
 */
class FieldLayoutTest extends FastTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'field_ui',
    'ds',
    'field_layout',
  ];

  /**
   * Tests that the entity view works when field layout is enabled
   */
  public function testCompatibility() {
    // Create a node.
    $settings = ['type' => 'article'];
    /* @var \Drupal\node\NodeInterface $node */
    $node = $this->drupalCreateNode($settings);

    $fields = [
      'fields[node_title][region]' => 'right',
    ];

    $this->dsSelectLayout();
    $this->dsConfigureUi($fields);

    $this->drupalGet('node/' . $node->id());

    // Assert that the title is visible.
    $elements = $this->xpath('//div[@class="field field--name-node-title field--type-ds field--label-hidden field__item"]/h2');
    $this->assertEquals(count($elements), 1);
  }

}
