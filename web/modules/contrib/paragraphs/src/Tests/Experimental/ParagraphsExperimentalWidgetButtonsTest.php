<?php

namespace Drupal\paragraphs\Tests\Experimental;

use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * Tests paragraphs experimental widget buttons.
 *
 * @group paragraphs
 */
class ParagraphsExperimentalWidgetButtonsTest extends ParagraphsExperimentalTestBase {

  use FieldUiTestTrait;
  use ParagraphsTestBaseTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'paragraphs_test',
  ];

  /**
   * Tests the widget buttons of paragraphs.
   */
  public function testWidgetButtons() {
    $this->addParagraphedContentType('paragraphed_test');

    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content']);
    // Add a Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    $this->addParagraphsType('text');

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);
    $edit = [
      'fields[field_paragraphs][type]' => 'paragraphs',
    ];
    $this->drupalPostForm('admin/structure/types/manage/paragraphed_test/form-display', $edit, t('Save'));
    $this->drupalPostAjaxForm('node/add/paragraphed_test', [], 'field_paragraphs_text_paragraph_add_more');

    // Create a node with a Paragraph.
    $text = 'recognizable_text';
    $edit = [
      'title[0][value]' => 'paragraphs_mode_test',
      'field_paragraphs[0][subform][field_text][0][value]' => $text,
    ];
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_text_paragraph_add_more');
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle('paragraphs_mode_test');

    // Test the 'Open' edit mode.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldByName('field_paragraphs[0][subform][field_text][0][value]', $text);
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText($text);

    // Test the 'Closed' edit mode.
    $this->setParagraphsWidgetMode('paragraphed_test', 'field_paragraphs', 'closed');
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Click "Edit" button.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_edit');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_1_edit');
    $this->assertFieldByName('field_paragraphs[0][subform][field_text][0][value]', $text);
    $closed_mode_text = 'closed_mode_text';
    // Click "Collapse" button on both paragraphs.
    $edit = ['field_paragraphs[0][subform][field_text][0][value]' => $closed_mode_text];
    $this->drupalPostAjaxForm(NULL, $edit, 'field_paragraphs_0_collapse');
    $edit = ['field_paragraphs[1][subform][field_text][0][value]' => $closed_mode_text];
    $this->drupalPostAjaxForm(NULL, $edit, 'field_paragraphs_1_collapse');
    // Verify that we have warning message for each paragraph.
    $this->assertEqual(2, count($this->xpath("//*[contains(@class, 'paragraphs-icon-changed')]")));
    $this->assertRaw('<div class="paragraphs-collapsed-description">' . $closed_mode_text);
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText('paragraphed_test ' . $node->label() . ' has been updated.');
    $this->assertText($closed_mode_text);

    // Test the 'Preview' closed mode.
    $this->setParagraphsWidgetSettings('paragraphed_test', 'field_paragraphs', ['closed_mode' => 'preview']);
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Click "Edit" button.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_edit');
    $this->assertFieldByName('field_paragraphs[0][subform][field_text][0][value]', $closed_mode_text);
    $preview_mode_text = 'preview_mode_text';
    $edit = ['field_paragraphs[0][subform][field_text][0][value]' => $preview_mode_text];
    // Click "Collapse" button.
    $this->drupalPostAjaxForm(NULL, $edit, 'field_paragraphs_0_collapse');
    $this->assertText('You have unsaved changes on this Paragraph item.');
    $this->assertEqual(1, count($this->xpath("//*[contains(@class, 'paragraphs-icon-changed')]")));
    $this->assertText($preview_mode_text);
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText('paragraphed_test ' . $node->label() . ' has been updated.');
    $this->assertText($preview_mode_text);

    // Test the remove function.
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Click "Remove" button.
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_0_remove');
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText('paragraphed_test ' . $node->label() . ' has been updated.');
    $this->assertNoText($preview_mode_text);
  }

  /**
   * Tests if buttons are present for each widget mode.
   */
  public function testButtonsVisibility() {
    $this->addParagraphedContentType('paragraphed_test');

    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content']);
    // Add a Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    $this->addParagraphsType('text');

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);
    $edit = [
      'fields[field_paragraphs][type]' => 'paragraphs',
    ];
    $this->drupalPostForm('admin/structure/types/manage/paragraphed_test/form-display', $edit, t('Save'));
    $this->drupalPostAjaxForm('node/add/paragraphed_test', [], 'field_paragraphs_text_paragraph_add_more');

    // Create a node with a Paragraph.
    $text = 'recognizable_text';
    $edit = [
      'title[0][value]' => 'paragraphs_mode_test',
      'field_paragraphs[0][subform][field_text][0][value]' => $text,
    ];
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_text_paragraph_add_more');
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $node = $this->drupalGetNodeByTitle('paragraphs_mode_test');

    // Checking visible buttons on "Open" mode.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertField('field_paragraphs_0_collapse');
    $this->assertField('field_paragraphs_0_remove');
    $this->assertField('field_paragraphs_0_duplicate');

    // Checking visible buttons on "Closed" mode.
    $this->setParagraphsWidgetMode('paragraphed_test', 'field_paragraphs', 'closed');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertField('field_paragraphs_0_edit');
    $this->assertField('field_paragraphs_0_remove');
    $this->assertField('field_paragraphs_0_duplicate');

    // Checking visible buttons on "Preview" mode.
    $this->setParagraphsWidgetMode('paragraphed_test', 'field_paragraphs', 'closed');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertField('field_paragraphs_0_edit');
    $this->assertField('field_paragraphs_0_remove');
    $this->assertField('field_paragraphs_0_duplicate');

    // Checking always show collapse and edit actions.
    $this->addParagraphsType('nested_paragraph');
    static::fieldUIAddNewField('admin/structure/paragraphs_type/nested_paragraph', 'nested', 'Nested', 'field_ui:entity_reference_revisions:paragraph', [
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ], []);
    $this->drupalGet('admin/structure/paragraphs_type/nested_paragraph/form-display');
    $edit = [
      'fields[field_nested][type]' => 'paragraphs',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_nested_paragraph_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_2_subform_field_nested_nested_paragraph_add_more');
    // Collapse is present on each nesting level.
    $this->assertFieldByName('field_paragraphs_2_collapse');
    $this->assertFieldByName('field_paragraphs_2_subform_field_nested_0_collapse');

    // Tests hook_paragraphs_widget_actions_alter.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostForm(NULL, NULL, t('Add text'));
    $this->assertNoField('edit-field-paragraphs-0-top-links-test-button');
    \Drupal::state()->set('paragraphs_test_dropbutton', TRUE);
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostForm(NULL, NULL, t('Add text'));
    $this->assertNoField('edit-field-paragraphs-0-top-links-test-button');
  }

}
