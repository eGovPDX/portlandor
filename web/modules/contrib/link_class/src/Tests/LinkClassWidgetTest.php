<?php

namespace Drupal\link_class\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\link\LinkItemInterface;
use Drupal\simpletest\WebTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

/**
 * Tests link_class field widgets.
 *
 * @group Link_class
 */
class LinkClassWidgetTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_test',
    'link',
    'node',
    'field',
    'field_ui',
    'link_class',
  ];

  /**
   * A field to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The instance used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * Entity form display.
   *
   * @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   */
  protected $formDisplay;

  /**
   * Entity view display.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $viewDisplay;

  /**
   * A node created.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Disable checking config schema.
   *
   * @var bool
   *
   * @todo remove this when #2823679 has landed.
   * See https://www.drupal.org/node/2823679
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'article']);
    $this->drupalLogin($this->drupalCreateUser([
      'view test entity',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
      'administer nodes',
      'edit any article content',
    ]));
  }

  /**
   * Tests link class widget.
   */
  protected function testLinkClassWidget() {
    $field_name = Unicode::strtolower($this->randomMachineName());
    // Create a field with settings to validate.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'link',
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'article',
      'label' => 'Link',
      'settings' => [
        'title' => DRUPAL_REQUIRED,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ],
    ]);
    $this->field->save();

    $this->setFormDisplay('node.article.default', 'node', 'article', $field_name, 'link_class_field_widget', []);
    $this->setViewDisplay('node.article.default', 'node', 'article', $field_name, 'link', []);

    $bundle_path = 'admin/structure/types/manage/article';

    $this->drupalGet($bundle_path . '/form-display');
    $this->assertFieldByXPath('//table[@id="field-display-overview"]//tr[@id="' . $field_name . '"]/td[1]', 'Link', 'Field appears in the Manage form display page.');
    $this->assertFieldByName('fields[' . $field_name . '][type]', 'link_class_field_widget', 'The expected widget is selected.');
    // Default values are applied on widget.
    $this->assertRaw(t('Mode: Users can set a class manually'));

    // Check the display mode.
    $this->drupalGet($bundle_path . '/display');
    $this->assertFieldByName('fields[' . $field_name . '][type]', 'link', 'The expected formatter is selected.');

    // Display creation form.
    $this->drupalGet('node/add/article');
    $this->assertFieldByName("{$field_name}[0][uri]", '', 'Link URL field is displayed');
    $this->assertFieldByName("{$field_name}[0][title]", '', 'Title URL field is displayed');
    $this->assertFieldByName("{$field_name}[0][options][attributes][class]", '', 'Classes input field is displayed');

    // Change the field configuration to set default value for link field.
    $field_path = "admin/structure/types/manage/article/fields/node.article.{$field_name}";
    $field_edit = [
      'default_value_input[' . $field_name . '][0][uri]' => 'http://www.mysite.fr',
      'default_value_input[' . $field_name . '][0][title]' => 'My secondary button',
      'default_value_input[' . $field_name . '][0][options][attributes][class]' => 'btn btn-secondary',
    ];
    $this->drupalPostForm($field_path, $field_edit, t('Save settings'));

    // Check that default value are here when we create a new node..
    $this->drupalGet('node/add/article');
    $this->assertRaw('http://www.mysite.fr');
    $this->assertRaw('My secondary button');
    $this->assertRaw('btn btn-secondary');

    // Create a test node.
    $title = 'Article 1';
    $values = [
      'type' => 'article',
      'title' => $title,
      'status' => 1,
      'body' => [
        'value' => 'Content body for ' . $title,
      ],
    ];
    $this->node = $this->drupalCreateNode($values);
    $this->drupalGet("node/{$this->node->id()}");
    $this->assertRaw(t('Article 1'));

    // Add a link with the node edit form.
    $edit = [
      $field_name . '[0][uri]' => 'http://www.example.com',
      $field_name . '[0][title]' => 'My button',
      $field_name . '[0][options][attributes][class]' => 'button1',
    ];
    $this->drupalPostForm("/node/{$this->node->id()}/edit", $edit, t('Save and keep published'));
    $this->drupalGet("node/{$this->node->id()}");
    $this->assertRaw('<a href="http://www.example.com" class="button1">My button</a>');

    // Change the widget settings to add class automatically.
    $this->drupalGet($bundle_path . '/form-display');
    // Click on the field link settings.
    $this->drupalPostAjaxForm(NULL, [], "{$field_name}_settings_edit");
    $edit = [
      "fields[{$field_name}][settings_edit_form][settings][link_class_mode]" => 'force_class',
      "fields[{$field_name}][settings_edit_form][settings][link_class_force]" => 'btn btn-default',
    ];
    foreach ($edit as $key => $value) {
      $this->assertField($key);
    }
    $this->drupalPostAjaxForm(NULL, $edit, "{$field_name}_plugin_settings_update");
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->drupalGet($bundle_path . '/form-display');
    $this->assertRaw(t('Mode: Class are automatically added'));
    $this->assertRaw(t('Class(es) added: btn btn-default'));

    // Edit the link in the node edit form.
    $this->drupalGet("node/{$this->node->id()}/edit");
    $this->assertNoField($field_name . '[0][options][attributes][class');
    $edit = [
      $field_name . '[0][uri]' => 'http://www.example.com',
      $field_name . '[0][title]' => 'My button',
    ];
    $this->drupalPostForm("/node/{$this->node->id()}/edit", $edit, t('Save and keep published'));
    $this->assertRaw('<a href="http://www.example.com" class="btn btn-default">My button</a>');

    // Change the widget settings to use a select list.
    $this->drupalGet($bundle_path . '/form-display');
    // Click on the field link settings.
    $this->drupalPostAjaxForm(NULL, [], "{$field_name}_settings_edit");
    $edit = [
      "fields[{$field_name}][settings_edit_form][settings][link_class_mode]" => 'select_class',
      "fields[{$field_name}][settings_edit_form][settings][link_class_select]" => 'btn btn-default|Default button' . PHP_EOL . 'btn btn-primary|Primary button' . PHP_EOL . 'btn btn-secondary|Secondary button',
    ];
    foreach ($edit as $key => $value) {
      $this->assertField($key);
    }
    $this->drupalPostAjaxForm(NULL, $edit, "{$field_name}_plugin_settings_update");
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->drupalGet($bundle_path . '/form-display');
    $this->assertRaw(t('Mode: Let users select a class from a list'));
    $this->assertRaw(t('Class(es) available: btn btn-default, btn btn-primary, btn btn-secondary'));

    // Edit the link in the node edit form.
    $this->drupalGet("node/{$this->node->id()}/edit");
    $this->assertField($field_name . '[0][options][attributes][class]');
    $this->assertOption("edit-{$field_name}-0-options-attributes-class", 'btn btn-default');
    $this->assertOption("edit-{$field_name}-0-options-attributes-class", 'btn btn-primary');
    $this->assertOption("edit-{$field_name}-0-options-attributes-class", 'btn btn-secondary');
    $this->assertOptionByText("edit-{$field_name}-0-options-attributes-class", 'Default button');
    $this->assertOptionByText("edit-{$field_name}-0-options-attributes-class", 'Primary button');
    $this->assertOptionByText("edit-{$field_name}-0-options-attributes-class", 'Secondary button');
    $edit = [
      $field_name . '[0][uri]' => 'http://www.example.com',
      $field_name . '[0][title]' => 'My button',
      $field_name . '[0][options][attributes][class]' => 'btn btn-primary',
    ];
    $this->drupalPostForm("/node/{$this->node->id()}/edit", $edit, t('Save and keep published'));
    $this->assertRaw('<a href="http://www.example.com" class="btn btn-primary">My button</a>');

    $this->drupalGet("node/{$this->node->id()}/edit");
    $this->assertOptionSelected("edit-{$field_name}-0-options-attributes-class", 'btn btn-primary');

  }

  /**
   * Set the widget for a component in a form display.
   *
   * @param string $form_display_id
   *   The form display id.
   * @param string $entity_type
   *   The entity type name.
   * @param string $bundle
   *   The bundle name.
   * @param string $field_name
   *   The field name to set.
   * @param string $widget_id
   *   The widget id to set.
   * @param array $settings
   *   The settings of widget.
   * @param string $mode
   *   The mode name.
   */
  protected function setFormDisplay($form_display_id, $entity_type, $bundle, $field_name, $widget_id, array $settings, $mode = 'default') {
    // Set article's form display.
    $this->formDisplay = EntityFormDisplay::load($form_display_id);

    if (!$this->formDisplay) {
      EntityFormDisplay::create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => $mode,
        'status' => TRUE,
      ])->save();
      $this->formDisplay = EntityFormDisplay::load($form_display_id);
    }
    if ($this->formDisplay instanceof EntityFormDisplayInterface) {
      $this->formDisplay->setComponent($field_name, [
        'type' => $widget_id,
        'settings' => $settings,
      ])->save();
    }
  }

  /**
   * Set the widget for a component in a View display.
   *
   * @param string $form_display_id
   *   The form display id.
   * @param string $entity_type
   *   The entity type name.
   * @param string $bundle
   *   The bundle name.
   * @param string $field_name
   *   The field name to set.
   * @param string $formatter_id
   *   The formatter id to set.
   * @param array $settings
   *   The settings of widget.
   * @param string $mode
   *   The mode name.
   */
  protected function setViewDisplay($form_display_id, $entity_type, $bundle, $field_name, $formatter_id, array $settings, $mode = 'default') {
    // Set article's view display.
    $this->viewDisplay = EntityViewDisplay::load($form_display_id);
    if (!$this->viewDisplay) {
      EntityViewDisplay::create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => $mode,
        'status' => TRUE,
      ])->save();
      $this->viewDisplay = EntityViewDisplay::load($form_display_id);
    }
    if ($this->viewDisplay instanceof EntityViewDisplayInterface) {
      $this->viewDisplay->setComponent($field_name, [
        'type' => $formatter_id,
        'settings' => $settings,
      ])->save();
    }

  }

}
