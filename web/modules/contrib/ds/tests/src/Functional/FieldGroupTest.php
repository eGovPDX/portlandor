<?php

namespace Drupal\Tests\ds\Functional;

use Drupal\field_group\Tests\FieldGroupTestTrait;

/**
 * Tests for field group integration with Display Suite.
 *
 * @group ds
 */
class FieldGroupTest extends FastTestBase {

  use FieldGroupTestTrait;

  /**
   * Test tabs.
   */
  public function testFieldPlugin() {
    // Create a node.
    $settings = ['type' => 'article', 'promote' => 1];
    /* @var \Drupal\node\NodeInterface $node */
    $node = $this->drupalCreateNode($settings);

    // Configure layout.
    $layout = [
      'layout' => 'ds_2col',
    ];
    $layout_assert = [
      'regions' => [
        'left' => '<td colspan="8">' . t('Left') . '</td>',
        'right' => '<td colspan="8">' . t('Right') . '</td>',
      ],
    ];
    $this->dsSelectLayout($layout, $layout_assert);

    $data = [
      'weight' => '1',
      'label' => 'Link',
      'format_type' => 'html_element',
      'format_settings' => [
        'label' => 'Link',
        'element' => 'div',
        'id' => 'wrapper-id',
        'classes' => 'test-class',
      ],
    ];
    $group = $this->createGroup('node', 'article', 'view', 'default', $data);

    $fields = [
      'fields[' . $group->group_name . '][region]' => 'right',
      'fields[body][region]' => 'right',
    ];
    $this->dsConfigureUi($fields);

    $fields = [
      'fields[body][parent]' => $group->group_name,
    ];
    $this->dsConfigureUi($fields);

    $this->drupalGet('node/' . $node->id());

    $elements = $this->xpath("//div[contains(@class, 'group-right')]/div");

    $this->assertTrue($elements[0]->hasClass('test-class'));
    $this->assertEquals($elements[0]->getAttribute('id'), 'wrapper-id');
  }

}
