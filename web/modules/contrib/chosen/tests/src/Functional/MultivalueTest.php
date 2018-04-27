<?php

namespace Drupal\Tests\chosen\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\UserCreationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that multivalue select fields are handled properly.
 *
 * @group chosen
 */
class MultivalueTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['chosen', 'options', 'node'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Enable chosen for all multiselect fields.
    $this->container->get('config.factory')
      ->getEditable('chosen.settings')
      ->set('minimum_multiple', 0)
      ->save();

    // Add an 'article' content type.
    $this->createContentType(['type' => 'article']);

    // Login an admin user.
    $user = $this->drupalCreateUser(['access content', 'bypass node access']);
    $this->drupalLogin($user);

    // Add a multiple select field.
    $storage = FieldStorageConfig::create([
      'type' => 'list_string',
      'entity_type' => 'node',
      'field_name' => 'test_multiselect',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ]);
    $storage->setSetting('allowed_values', [
      'one' => 'One',
      'two' => 'Two',
    ]);
    $storage->save();
    $field = FieldConfig::create([
      'field_name' => 'test_multiselect',
      'bundle' => 'article',
      'entity_type' => 'node',
    ]);
    $field->save();

    entity_get_form_display('node', 'article', 'default')
      ->setComponent('test_multiselect', ['type' => 'options_select'])
      ->save();
  }

  /**
   * Tests that the _none option is removed.
   */
  public function testNoneOption() {
    $this->drupalGet('node/add/article');
    $this->assertSession()->responseNotContains('_none');
  }

}
