<?php

namespace Drupal\Tests\ds\Functional;

/**
 * Tests DS layout plugins.
 *
 * @group ds
 */
class LayoutFluidTest extends FastTestBase {

  /**
   * Test fluid Display Suite layouts.
   */
  public function testFluidLayout() {
    // Assert our 2 tests layouts are found.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertSession()->responseContains('Test Fluid two column');

    $layout = [
      'layout' => 'dstest_2col_fluid',
    ];

    $assert = [
      'regions' => [
        'left' => '<td colspan="8">' . t('Left') . '</td>',
        'right' => '<td colspan="8">' . t('Right') . '</td>',
      ],
    ];

    $fields = [
      'fields[node_author][region]' => 'left',
      'fields[node_links][region]' => 'left',
      'fields[body][region]' => 'left',
    ];

    $this->dsSelectLayout($layout, $assert);
    $this->dsConfigureUi($fields);

    // Create a node.
    $settings = ['type' => 'article'];
    $node = $this->drupalCreateNode($settings);

    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('group-left');
    $this->assertSession()->responseNotContains('group-right');
    $this->assertSession()->responseContains('group-one-column');
    $this->assertSession()->responseContains('dstest-2col-fluid.css');

    // Add fields to the right column.
    $fields = [
      'fields[node_author][region]' => 'left',
      'fields[node_links][region]' => 'left',
      'fields[body][region]' => 'right',
    ];

    $this->dsSelectLayout($layout, $assert);
    $this->dsConfigureUi($fields);

    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('group-left');
    $this->assertSession()->responseContains('group-right');
    $this->assertSession()->responseNotContains('group-one-column');

    // Move all fields to the right column.
    $fields = [
      'fields[node_author][region]' => 'right',
      'fields[node_links][region]' => 'right',
      'fields[heavy_field][region]' => 'right',
      'fields[body][region]' => 'right',
    ];

    $this->dsSelectLayout($layout, $assert);
    $this->dsConfigureUi($fields);

    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseNotContains('group-left');
    $this->assertSession()->responseContains('group-right');
    $this->assertSession()->responseContains('group-one-column');

    // Remove the css
    $fields = [
      'disable_css' => TRUE,
    ];

    $this->dsSelectLayout($layout, $assert);
    $this->dsConfigureUi($fields);

    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseNotContains('dstest-2col-fluid.css');
  }

}
