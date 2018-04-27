<?php

namespace Drupal\chosen_field\Tests;

use Drupal\field\Tests\FieldTestBase;

/**
 * Test the Chosen widgets.
 *
 * @group Chosen
 */
class ChosenFieldWidgetsTest extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'node',
    'options',
    'entity_test',
    'taxonomy',
    'field_ui',
    'options_test',
    'chosen_field',
  );

  /**
   * A field with cardinality 1 to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $card_1;

  /**
   * A field with cardinality 2 to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $card_2;

  /**
   * Function used to setup before running the test.
   */
  protected function setUp() {
    parent::setUp();

    // Field storage with cardinality 1.
    $this->card_1 = \Drupal::entityTypeManager()->getStorage('field_storage_config')->create([
      'field_name' => 'card_1',
      'entity_type' => 'entity_test',
      'type' => 'list_integer',
      'cardinality' => 1,
      'settings' => [
        'allowed_values' => [
          // Make sure that 0 works as an option.
          0 => 'Zero',
          1 => 'One',
          // Make sure that option text is properly sanitized.
          2 => 'Some <script>dangerous</script> & unescaped <strong>markup</strong>',
          // Make sure that HTML entities in option text are not double-encoded.
          3 => 'Some HTML encoded markup with &lt; &amp; &gt;',
        ],
      ],
    ]);
    $this->card_1->save();

    // Field storage with cardinality 2.
    $this->card_2 = \Drupal::entityTypeManager()->getStorage('field_storage_config')->create([
      'field_name' => 'card_2',
      'entity_type' => 'entity_test',
      'type' => 'list_integer',
      'cardinality' => 2,
      'settings' => [
        'allowed_values' => [
          // Make sure that 0 works as an option.
          0 => 'Zero',
          1 => 'One',
          // Make sure that option text is properly sanitized.
          2 => 'Some <script>dangerous</script> & unescaped <strong>markup</strong>',
        ],
      ],
    ]);
    $this->card_2->save();

    // Create a web user.
    $this->drupalLogin($this->drupalCreateUser(['view test entity', 'administer entity_test content']));
  }

  /**
   * Tests the 'chosen_select' widget (single select).
   */
  public function testSelectListSingle() {
    // Create an instance of the 'single value' field.
    $instance = \Drupal::entityTypeManager()->getStorage('field_config')->create([
      'field_storage' => $this->card_1,
      'bundle' => 'entity_test',
    ]);
    $instance->setRequired(TRUE);
    $instance->save();

    \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('entity_test.entity_test.default')
      ->setComponent($this->card_1->getName(), [
        'type' => 'chosen_select',
      ])
      ->save();

    // Create an entity.
    $entity = \Drupal::entityTypeManager()->getStorage('entity_test')->create(array(
      'user_id' => 1,
      'name' => $this->randomMachineName(),
    ));
    $entity->save();
    $entity_init = clone $entity;

    // Display form.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    // A required field without any value has a "none" option.
    $this->assertTrue($this->xpath('//select[@id=:id]//option[@value="_none" and text()=:label]', array(':id' => 'edit-card-1', ':label' => t('- Select a value -'))), 'A non-required select list has a "Select a value" choice.');

    // With no field data, nothing is selected.
    $this->assertNoOptionSelected('edit-card-1', '_none');
    $this->assertNoOptionSelected('edit-card-1', 0);
    $this->assertNoOptionSelected('edit-card-1', 1);
    $this->assertNoOptionSelected('edit-card-1', 2);
    $this->assertRaw('Some dangerous &amp; unescaped markup', 'Option text was properly filtered.');

    // Submit form: select invalid 'none' option.
    $edit = array('card_1' => '_none');
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw(t('@title field is required.', array('@title' => $instance->getName())), 'Cannot save a required field when selecting "none" from the select list.');

    // Submit form: select first option.
    $edit = array('card_1' => 0);
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_1', array(0));

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    // A required field with a value has no 'none' option.
    $this->assertFalse($this->xpath('//select[@id=:id]//option[@value="_none"]', array(':id' => 'edit-card-1')), 'A required select list with an actual value has no "none" choice.');
    $this->assertOptionSelected('edit-card-1', 0);
    $this->assertNoOptionSelected('edit-card-1', 1);
    $this->assertNoOptionSelected('edit-card-1', 2);

    // Make the field non required.
    $instance->setRequired(FALSE);
    $instance->save();

    // Display form.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    // A non-required field has a 'none' option.
    $this->assertTrue($this->xpath('//select[@id=:id]//option[@value="_none" and text()=:label]', array(':id' => 'edit-card-1', ':label' => t('- None -'))), 'A non-required select list has a "None" choice.');
    // Submit form: Unselect the option.
    $edit = array('card_1' => '_none');
    $this->drupalPostForm('entity_test/manage/' . $entity->id() . '/edit', $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_1', array());

    // Test optgroups.
    $this->card_1->setSetting('allowed_values', []);
    $this->card_1->setSetting('allowed_values_function', 'options_test_allowed_values_callback');
    $this->card_1->save();

    // Display form: with no field data, nothing is selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertNoOptionSelected('edit-card-1', 0);
    $this->assertNoOptionSelected('edit-card-1', 1);
    $this->assertNoOptionSelected('edit-card-1', 2);
    $this->assertRaw('Some dangerous &amp; unescaped markup', 'Option text was properly filtered.');
    $this->assertRaw('Group 1', 'Option groups are displayed.');

    // Submit form: select first option.
    $edit = array('card_1' => 0);
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_1', array(0));

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertOptionSelected('edit-card-1', 0);
    $this->assertNoOptionSelected('edit-card-1', 1);
    $this->assertNoOptionSelected('edit-card-1', 2);

    // Submit form: Unselect the option.
    $edit = array('card_1' => '_none');
    $this->drupalPostForm('entity_test/manage/' . $entity->id() . '/edit', $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_1', array());
  }

  /**
   * Tests the 'options_select' widget (multiple select).
   */
  function testSelectListMultiple() {
    // Create an instance of the 'multiple values' field.
    $instance = \Drupal::entityTypeManager()->getStorage('field_config')->create([
      'field_storage' => $this->card_2,
      'bundle' => 'entity_test',
    ]);
    $instance->save();

    \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('entity_test.entity_test.default')
      ->setComponent($this->card_2->getName(), [
        'type' => 'chosen_select',
      ])
      ->save();

    // Create an entity.
    $entity = \Drupal::entityTypeManager()->getStorage('entity_test')->create(array(
      'user_id' => 1,
      'name' => $this->randomMachineName(),
    ));
    $entity->save();
    $entity_init = clone $entity;

    // Display form: with no field data, nothing is selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertNoOptionSelected('edit-card-2', 0);
    $this->assertNoOptionSelected('edit-card-2', 1);
    $this->assertNoOptionSelected('edit-card-2', 2);
    $this->assertRaw('Some dangerous &amp; unescaped markup', 'Option text was properly filtered.');

    // Submit form: select first and third options.
    $edit = array('card_2[]' => array(0 => 0, 2 => 2));
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', array(0, 2));

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertOptionSelected('edit-card-2', 0);
    $this->assertNoOptionSelected('edit-card-2', 1);
    $this->assertOptionSelected('edit-card-2', 2);

    // Submit form: select only first option.
    $edit = array('card_2[]' => array(0 => 0));
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', array(0));

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertOptionSelected('edit-card-2', 0);
    $this->assertNoOptionSelected('edit-card-2', 1);
    $this->assertNoOptionSelected('edit-card-2', 2);

    // Submit form: select the three options while the field accepts only 2.
    $edit = array('card_2[]' => array(0 => 0, 1 => 1, 2 => 2));
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('this field cannot hold more than 2 values', 'Validation error was displayed.');

    // Submit form: uncheck all options.
    $edit = array('card_2[]' => array());
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', array());

    // A required select list does not have an empty key.
    $instance->setRequired(TRUE);
    $instance->save();
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertFalse($this->xpath('//select[@id=:id]//option[@value=""]', array(':id' => 'edit-card-2')), 'A required select list does not have an empty key.');

    // We do not have to test that a required select list with one option is
    // auto-selected because the browser does it for us.
    // Test optgroups.
    // Use a callback function defining optgroups.
    $this->card_2->setSetting('allowed_values', []);
    $this->card_2->setSetting('allowed_values_function', 'options_test_allowed_values_callback');
    $this->card_2->save();

    $instance->setRequired(FALSE);
    $instance->save();

    // Display form: with no field data, nothing is selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertNoOptionSelected('edit-card-2', 0);
    $this->assertNoOptionSelected('edit-card-2', 1);
    $this->assertNoOptionSelected('edit-card-2', 2);
    $this->assertRaw('Some dangerous &amp; unescaped markup', 'Option text was properly filtered.');
    $this->assertRaw('Group 1', 'Option groups are displayed.');

    // Submit form: select first option.
    $edit = array('card_2[]' => array(0 => 0));
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertFieldValues($entity_init, 'card_2', array(0));

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertOptionSelected('edit-card-2', 0);
    $this->assertNoOptionSelected('edit-card-2', 1);
    $this->assertNoOptionSelected('edit-card-2', 2);
  }

}
