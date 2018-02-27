<?php

namespace Drupal\Tests\ds\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Tests for managing layouts and classes on Field UI screen.
 *
 * @group ds
 */
class LayoutClassesTest extends FastTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setup() {
    parent::setup();

    // Set extra fields.
    \Drupal::configFactory()->getEditable('ds_extras.settings')
      ->set('region_to_block', TRUE)
      ->set('fields_extra', TRUE)
      ->set('fields_extra_list', ['node|article|ds_extras_extra_test_field', 'node|article|ds_extras_second_field'])
      ->save();

    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
  }

  /**
   * Test selecting layouts, classes, region to block and fields.
   */
  public function testDsTestLayouts() {
    // Check that the ds_3col_equal_width layout is not available (through the
    // alter).
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertSession()->responseNotContains('ds_3col_stacked_equal_width');

    // Create code and block field.
    $this->dsCreateTokenField();
    $this->dsCreateBlockField();

    $layout = [
      'layout' => 'ds_2col_stacked',
    ];

    $assert = [
      'regions' => [
        'header' => '<td colspan="8">' . t('Header') . '</td>',
        'left' => '<td colspan="8">' . t('Left') . '</td>',
        'right' => '<td colspan="8">' . t('Right') . '</td>',
        'footer' => '<td colspan="8">' . t('Footer') . '</td>',
      ],
    ];

    $fields = [
      'fields[node_post_date][region]' => 'header',
      'fields[node_author][region]' => 'left',
      'fields[node_links][region]' => 'left',
      'fields[body][region]' => 'right',
      'fields[dynamic_token_field:node-test_field][region]' => 'left',
      'fields[dynamic_block_field:node-test_block_field][region]' => 'left',
      'fields[node_submitted_by][region]' => 'left',
      'fields[ds_extras_extra_test_field][region]' => 'header',
    ];

    // Setup first layout.
    $this->dsSelectLayout($layout, $assert);
    $this->dsConfigureClasses();
    $this->dsSelectClasses();
    $this->dsConfigureUi($fields);

    // Assert the two extra fields are found.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertSession()->responseContains('ds_extras_extra_test_field');
    $this->assertSession()->responseContains('ds_extras_second_field');

    // Assert we have configuration.
    $entity_manager = \Drupal::entityTypeManager();
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity_display */
    $entity_display = $entity_manager->getStorage('entity_view_display')->load('node.article.default');
    $data = $entity_display->getThirdPartySettings('ds');

    $this->assertTrue(!empty($data), t('Configuration found for layout settings for node article'));
    $this->assertTrue(in_array('ds_extras_extra_test_field', $data['regions']['header']), t('Extra field is in header'));
    $this->assertTrue(in_array('node_post_date', $data['regions']['header']), t('Post date is in header'));
    $this->assertTrue(in_array('dynamic_token_field:node-test_field', $data['regions']['left']), t('Test field is in left'));
    $this->assertTrue(in_array('node_author', $data['regions']['left']), t('Author is in left'));
    $this->assertTrue(in_array('node_links', $data['regions']['left']), t('Links is in left'));
    $this->assertTrue(in_array('dynamic_block_field:node-test_block_field', $data['regions']['left']), t('Test block field is in left'));
    $this->assertTrue(in_array('body', $data['regions']['right']), t('Body is in right'));
    $this->assertTrue(in_array('class_name_1', $data['layout']['settings']['classes']['header']), t('Class name 1 is in header'));
    $this->assertTrue(empty($data['layout']['settings']['classes']['left']), t('Left has no classes'));
    $this->assertTrue(empty($data['layout']['settings']['classes']['right']), t('Right has classes'));
    $this->assertTrue(in_array('class_name_2', $data['layout']['settings']['classes']['footer']), t('Class name 2 is in header'));

    // Create a article node and verify settings.
    $settings = [
      'type' => 'article',
    ];
    $node = $this->drupalCreateNode($settings);
    $this->drupalGet('node/' . $node->id());

    // Assert default classes.
    $this->assertSession()->responseContains('node node--type-article node--view-mode-full');

    // Assert regions.
    $this->assertSession()->responseContains('group-header');
    $this->assertSession()->responseContains('class_name_1 group-header');
    $this->assertSession()->responseContains('group-left');
    $this->assertSession()->responseContains('group-right');
    $this->assertSession()->responseContains('group-footer');
    $this->assertSession()->responseContains('class_name_2 group-footer');

    // Assert custom fields.
    $this->assertSession()->responseContains('field--name-dynamic-token-fieldnode-test-field');
    $this->assertSession()->responseContains('field--name-dynamic-block-fieldnode-test-block-field');

    $this->assertSession()->responseContains('Submitted by');
    $this->assertSession()->pageTextContains('This is an extra field made available through "Extra fields" functionality.');

    // Test HTML5 wrappers.
    $this->assertSession()->responseNotContains('<header class="class_name_1 group-header');
    $this->assertSession()->responseNotContains('<footer class="group-right');
    $this->assertSession()->responseNotContains('<article');
    $wrappers = [
      'layout_configuration[region_wrapper][header]' => 'header',
      'layout_configuration[region_wrapper][right]' => 'footer',
      'layout_configuration[region_wrapper][outer_wrapper]' => 'article',
    ];
    $this->dsConfigureUi($wrappers);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('<header class="class_name_1 group-header');
    $this->assertSession()->responseContains('<footer class="group-right');
    $this->assertSession()->responseContains('<article');

    // Remove all the node classes.
    $edit = ['entity_classes' => 'no_classes'];
    $this->drupalPostForm('admin/structure/types/manage/article/display', $edit, t('Save'));
    $this->drupalGet('node/' . $node->id());

    // Assert that there are no entity classes.
    $this->assertSession()->responseNotContains('node node--type-article node--view-mode-full');

    // Only show view mode (deprecated).
    $edit = ['entity_classes' => 'old_view_mode'];
    $this->drupalPostForm('admin/structure/types/manage/article/display', $edit, t('Save'));
    $this->drupalGet('node/' . $node->id());

    // Assert that the old view mode class name is added (deprecated).
    $this->assertSession()->responseContains('view-mode-full');

    // Let's create a block field, enable the full mode first.
    $edit = ['display_modes_custom[full]' => '1'];
    $this->drupalPostForm('admin/structure/types/manage/article/display', $edit, t('Save'));

    // Select layout.
    $layout = [
      'layout' => 'ds_2col',
    ];

    $assert = [
      'regions' => [
        'left' => '<td colspan="8">' . t('Left') . '</td>',
        'right' => '<td colspan="8">' . t('Right') . '</td>',
      ],
    ];
    $this->dsSelectLayout($layout, $assert, 'admin/structure/types/manage/article/display/full');

    // Create new block field.
    $edit = [
      'new_block_region' => 'Block region',
      'new_block_region_key' => 'block_region',
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/display/full', $edit, t('Save'));
    $this->assertSession()->responseContains('<td colspan="9">' . t('Block region') . '</td>');

    // Configure fields.
    $fields = [
      'fields[node_author][region]' => 'left',
      'fields[node_links][region]' => 'left',
      'fields[body][region]' => 'right',
      'fields[dynamic_token_field:node-test_field][region]' => 'block_region',
    ];
    $this->dsConfigureUi($fields, 'admin/structure/types/manage/article/display/full');

    // Change layout via admin/structure/ds/layout-change.
    // First verify that header and footer are not here.
    $this->drupalGet('admin/structure/types/manage/article/display/full');
    $this->assertSession()->responseNotContains('<td colspan="8">' . t('Header') . '</td>');
    $this->assertSession()->responseNotContains('<td colspan="8">' . t('Footer') . '</td>');

    // Remap the regions.
    $edit = [
      'ds_left' => 'header',
      'ds_right' => 'footer',
      'ds_block_region' => 'footer',
    ];
    $this->drupalPostForm('admin/structure/ds/change-layout/node/article/full/ds_2col_stacked', $edit, t('Save'));
    $this->drupalGet('admin/structure/types/manage/article/display/full');

    // Verify new regions.
    $this->assertSession()->responseContains('<td colspan="9">' . t('Header') . '</td>');
    $this->assertSession()->responseContains('<td colspan="9">' . t('Footer') . '</td>');
    $this->assertSession()->responseContains('<td colspan="9">' . t('Block region') . '</td>');

    // Verify settings.
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity_display */
    $entity_display = EntityViewDisplay::load('node.article.full');
    $data = $entity_display->getThirdPartySettings('ds');
    $this->assertTrue(in_array('node_author', $data['regions']['header']), t('Author is in header'));
    $this->assertTrue(in_array('node_links', $data['regions']['header']), t('Links field is in header'));
    $this->assertTrue(in_array('body', $data['regions']['footer']), t('Body field is in footer'));
    $this->assertTrue(in_array('dynamic_token_field:node-test_field', $data['regions']['footer']), t('Test field is in footer'));

    // Test that a default view mode with no layout is not affected by a
    // disabled view mode.
    $edit = [
      'layout' => '_none',
      'display_modes_custom[full]' => FALSE,
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/display', $edit,'Save');

    $elements = $this->xpath('//*[@id="edit-fields-body-region"]');

    $this->assertTrue($elements[0]->find('xpath', '//option[@value = "content" and @selected = "selected"]'));
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextNotContains('Test code field on node 1');
  }

}
