<?php

namespace Drupal\Tests\ds\Functional;

/**
 * Tests for twig specific functionality.
 *
 * @group ds
 */
class TwigRenderTest extends FastTestBase {

  /**
   * Tests targeting the field in a twig template.
   */
  public function testFieldNameTargeting() {
    // Create a node.
    $settings = ['type' => 'article', 'promote' => 1];
    /* @var \Drupal\node\NodeInterface $node */
    $node = $this->drupalCreateNode($settings);

    // Configure layout.
    $layout = [
      'layout' => 'dstest_1col_title',
    ];
    $layout_assert = [
      'regions' => [
        'ds_content' => '<td colspan="8">' . t('Content') . '</td>',
      ],
    ];
    $this->dsSelectLayout($layout, $layout_assert);

    $fields = [
      'fields[node_title][region]' => 'ds_content',
    ];
    $this->dsConfigureUi($fields);

    $this->drupalGet('node/' . $node->id());

    // Assert that the title is visible.
    $elements = $this->xpath('//div[@class="field field--name-node-title field--type-ds field--label-hidden field__item"]/h2');
    $this->assertEquals(count($elements), 1);

    $this->assertSession()->pageTextContains($node->getTitle());

    $edit = [
      'fs3[use_field_names]' => FALSE,
    ];
    $this->drupalPostForm('admin/structure/ds/settings', $edit, 'Save configuration');

    $this->drupalGet('node/' . $node->id());

    // Assert that the title is not visible anymore.
    $elements = $this->xpath('//div[@class="field field--name-node-title field--type-ds field--label-hidden field__item"]/h2');
    $this->assertEquals(count($elements), 0);
  }

}
