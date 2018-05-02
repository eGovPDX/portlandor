<?php

namespace Drupal\Tests\field_group\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for displaying entities.
 *
 * @group field_group
 */
class EntityDisplayTest extends BrowserTestBase {

  use FieldGroupTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'field_test',
    'field_ui',
    'field_group',
    'field_group_test',
  ];

  /**
   * The node type id.
   *
   * @var string
   */
  protected $type;

  /**
   * A node to use for testing.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create test user.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
      'bypass node access'
    ]);
    $this->drupalLogin($admin_user);

    // Create content type, with underscores.
    $type_name = strtolower($this->randomMachineName(8)) . '_test';
    $type = $this->drupalCreateContentType(array('name' => $type_name, 'type' => $type_name));
    $this->type = $type->id();
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    $display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load('node' . '.' . $type_name . '.' . 'default');

    // Create a node.
    $node_values = array('type' => $type_name);

    // Create test fields.
    foreach (['field_test', 'field_test_2', 'field_no_access'] as $field_name) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'node',
        'type' => 'test_field',
      ]);
      $field_storage->save();

      $instance = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $type_name,
        'label' => $this->randomMachineName(),
      ]);
      $instance->save();

      // Assign a test value for the field.
      $node_values[$field_name][0]['value'] = mt_rand(1, 127);

      // Set the field visible on the display object.
      $display_options = array(
        'label' => 'above',
        'type' => 'field_test_default',
        'settings' => array(
          'test_formatter_setting' => $this->randomMachineName(),
        ),
      );
      $display->setComponent($field_name, $display_options);
    }

    // Save display + create node.
    $display->save();
    $this->node = $this->drupalCreateNode($node_values);
  }

  /**
   * Test field access for field groups.
   */
  public function testFieldAccess() {
    $data = array(
      'label' => 'Wrapper',
      'children' => array(
        0 => 'field_no_access',
      ),
      'format_type' => 'html_element',
      'format_settings' => array(
        'element' => 'div',
        'id' => 'wrapper-id',
      ),
    );

    $this->createGroup('node', $this->type, 'view', 'default', $data);
    $this->drupalGet('node/' . $this->node->id());

    // Test if group is not shown.
    $this->assertNoFieldByXPath("//div[contains(@id, 'wrapper-id')]", NULL, t('Div that contains fields with no access is not shown.'));
  }

  /**
   * Test the html element formatter.
   */
  public function testHtmlElement() {
    $data = array(
      'weight' => '1',
      'children' => array(
        0 => 'field_test',
        1 => 'body',
      ),
      'label' => 'Link',
      'format_type' => 'html_element',
      'format_settings' => array(
        'label' => 'Link',
        'element' => 'div',
        'id' => 'wrapper-id',
        'classes' => 'test-class',
      ),
    );
    $group = $this->createGroup('node', $this->type, 'view', 'default', $data);

    //$groups = field_group_info_groups('node', 'article', 'view', 'default', TRUE);
    $this->drupalGet('node/' . $this->node->id());

    // Test group ids and classes.
    $this->assertFieldByXPath("//div[contains(@id, 'wrapper-id')]", NULL, 'Wrapper id set on wrapper div');
    $this->assertFieldByXPath("//div[contains(@class, 'test-class')]", NULL, 'Test class set on wrapper div, class="' . $group->group_name . ' test-class');

    // Test group label.
    $this->assertNoRaw('<h3><span>' . $data['label'] . '</span></h3>');

    // Set show label to true.
    $group->format_settings['show_label'] = TRUE;
    $group->format_settings['label_element'] = 'h3';
    field_group_group_save($group);

    $this->drupalGet('node/' . $this->node->id());
    $this->assertRaw('<h3>' . $data['label'] . '</h3>');

    // Change to collapsible with blink effect.
    $group->format_settings['effect'] = 'blink';
    $group->format_settings['speed'] = 'fast';
    field_group_group_save($group);

    $this->drupalGet('node/' . $this->node->id());
    $this->assertFieldByXPath("//div[contains(@class, 'speed-fast')]", NULL, 'Speed class is set');
    $this->assertFieldByXPath("//div[contains(@class, 'effect-blink')]", NULL, 'Effect class is set');
  }

  /**
   * Test the fieldset formatter.
   */
  public function testFieldset() {
    $data = array(
      'weight' => '1',
      'children' => array(
        0 => 'field_test',
        1 => 'body',
      ),
      'label' => 'Test Fieldset',
      'format_type' => 'fieldset',
      'format_settings' => array(
        'id' => 'fieldset-id',
        'classes' => 'test-class',
        'description' => 'test description',
      ),
    );
    $this->createGroup('node', $this->type, 'view', 'default', $data);

    $this->drupalGet('node/' . $this->node->id());

    // Test group ids and classes.
    $this->assertFieldByXPath("//fieldset[contains(@id, 'fieldset-id')]", NULL, 'Correct id set on the fieldset');
    $this->assertFieldByXPath("//fieldset[contains(@class, 'test-class')]", NULL, 'Test class set on the fieldset');
  }

  /**
   * Test the tabs formatter.
   */
  public function testTabs() {
    $data = array(
      'label' => 'Tab 1',
      'weight' => '1',
      'children' => array(
        0 => 'field_test',
      ),
      'format_type' => 'tab',
      'format_settings' => array(
        'label' => 'Tab 1',
        'classes' => 'test-class',
        'description' => '',
        'formatter' => 'open',
      ),
    );
    $first_tab = $this->createGroup('node', $this->type, 'view', 'default', $data);

    $data = array(
      'label' => 'Tab 2',
      'weight' => '1',
      'children' => array(
        0 => 'field_test_2',
      ),
      'format_type' => 'tab',
      'format_settings' => array(
        'label' => 'Tab 1',
        'classes' => 'test-class-2',
        'description' => 'description of second tab',
        'formatter' => 'closed',
      ),
    );
    $second_tab = $this->createGroup('node', $this->type, 'view', 'default', $data);

    $data = array(
      'label' => 'Tabs',
      'weight' => '1',
      'children' => array(
        0 => $first_tab->group_name,
        1 => $second_tab->group_name,
      ),
      'format_type' => 'tabs',
      'format_settings' => array(
        'direction' => 'vertical',
        'label' => 'Tab 1',
        'classes' => 'test-class-wrapper',
      ),
    );
    $tabs_group = $this->createGroup('node', $this->type, 'view', 'default', $data);

    $this->drupalGet('node/' . $this->node->id());

    // Test properties.
    $this->assertFieldByXPath("//div[contains(@class, 'test-class-wrapper')]", NULL, 'Test class set on tabs wrapper');
    $this->assertFieldByXPath("//details[contains(@class, 'test-class-2')]", NULL, 'Test class set on second tab');
    $this->assertRaw('<div class="details-description">description of second tab</div>');

    // Test if correctly nested.
    $this->assertFieldByXPath("//div[contains(@class, 'test-class-wrapper')]//details[contains(@class, 'test-class')]", NULL, 'First tab is displayed as child of the wrapper.');
    $this->assertFieldByXPath("//div[contains(@class, 'test-class-wrapper')]//details[contains(@class, 'test-class-2')]", NULL, 'Second tab is displayed as child of the wrapper.');

    // Test if it's a vertical tab.
    $this->assertFieldByXPath('//div[@data-vertical-tabs-panes=""]', NULL, 'Tabs are shown vertical.');

    // Switch to horizontal
    $tabs_group->format_settings['direction'] = 'horizontal';
    field_group_group_save($tabs_group);

    $this->drupalGet('node/' . $this->node->id());

    // Test if it's a horizontal tab.
    $this->assertFieldByXPath('//div[@data-horizontal-tabs-panes=""]', NULL, 'Tabs are shown horizontal.');
  }

  /**
   * Test the accordion formatter.
   */
  public function testAccordion() {
    $data = array(
      'label' => 'Accordion item 1',
      'weight' => '1',
      'children' => array(
        0 => 'field_test',
      ),
      'format_type' => 'accordion_item',
      'format_settings' => array(
        'label' => 'Accordion item 1',
        'classes' => 'test-class',
        'formatter' => 'closed',
      ),
    );
    $first_item = $this->createGroup('node', $this->type, 'view', 'default', $data);

    $data = array(
      'label' => 'Accordion item 2',
      'weight' => '1',
      'children' => array(
        0 => 'field_test_2',
      ),
      'format_type' => 'accordion_item',
      'format_settings' => array(
        'label' => 'Tab 2',
        'classes' => 'test-class-2',
        'formatter' => 'open',
      ),
    );
    $second_item = $this->createGroup('node', $this->type, 'view', 'default', $data);

    $data = array(
      'label' => 'Accordion',
      'weight' => '1',
      'children' => array(
        0 => $first_item->group_name,
        1 => $second_item->group_name,
      ),
      'format_type' => 'accordion',
      'format_settings' => array(
        'label' => 'Tab 1',
        'classes' => 'test-class-wrapper',
        'effect' => 'bounceslide'
      ),
    );
    $this->createGroup('node', $this->type, 'view', 'default', $data);

    $this->drupalGet('node/' . $this->node->id());

    // Test properties.
    $this->assertFieldByXPath("//div[contains(@class, 'test-class-wrapper')]", NULL, 'Test class set on tabs wrapper');
    $this->assertFieldByXPath("//div[contains(@class, 'effect-bounceslide')]", NULL, 'Correct effect is set on the accordion');
    $this->assertFieldByXPath("//div[contains(@class, 'test-class')]", NULL, 'Accordion item with test-class is shown');
    $this->assertFieldByXPath("//div[contains(@class, 'test-class-2')]", NULL, 'Accordion item with test-class-2 is shown');
    $this->assertFieldByXPath("//h3[contains(@class, 'field-group-accordion-active')]", NULL, 'Accordion item 2 was set active');

    // Test if correctly nested
    $this->assertFieldByXPath("//div[contains(@class, 'test-class-wrapper')]//div[contains(@class, 'test-class')]", NULL, 'First item is displayed as child of the wrapper.');
    $this->assertFieldByXPath("//div[contains(@class, 'test-class-wrapper')]//div[contains(@class, 'test-class-2')]", NULL, 'Second item is displayed as child of the wrapper.');
  }

}
