<?php

namespace Drupal\Tests\schemata\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests requests schemata routes.
 *
 * @group Schemata
 */
class RequestTest extends BrowserTestBase {

  /**
   * Set to TRUE to run this test to generate expectation files.
   *
   * The test will be marked as a fail when generating test files.
   *
   * @var bool
   */
  protected $generateExpectationFiles = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'field',
    'filter',
    'text',
    'node',
    'taxonomy',
    'serialization',
    'hal',
    'schemata',
    'schemata_json_schema',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    if (!NodeType::load('camelids')) {
      // Create a "Camelids" node type.
      NodeType::create([
        'name' => 'Camelids',
        'type' => 'camelids',
      ])->save();
    }

    // Create a "Camelids" vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => 'Camelids',
      'vid' => 'camelids',
    ]);
    $vocabulary->save();

    $entity_types = ['node', 'taxonomy_term'];
    foreach ($entity_types as $entity_type) {
      // Add access-protected field.
      FieldStorageConfig::create([
        'entity_type' => $entity_type,
        'field_name' => 'field_test_' . $entity_type,
        'type' => 'text',
      ])
        ->setCardinality(1)
        ->save();
      FieldConfig::create([
        'entity_type' => $entity_type,
        'field_name' => 'field_test_' . $entity_type,
        'bundle' => 'camelids',
      ])
        ->setLabel('Test field')
        ->setTranslatable(FALSE)
        ->save();
    }
    $this->container->get('router.builder')->rebuild();
    $this->drupalLogin($this->drupalCreateUser(['access schemata data models']));
  }

  /**
   * Tests schemata requests.
   */
  public function testRequests() {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');

    foreach (['json', 'hal_json', 'api_json'] as $format) {
      foreach ($entity_type_manager->getDefinitions() as $entity_type_id => $entity_type) {
        $this->requestSchema($format, $entity_type_id);
        if ($bundle_type = $entity_type->getBundleEntityType()) {
          $bundles = $entity_type_manager->getStorage($bundle_type)->loadMultiple();
          foreach ($bundles as $bundle) {
            $this->requestSchema($format, $entity_type_id, $bundle->id());
          }
        }
      }
    }
    if ($this->generateExpectationFiles) {
      $this->fail('Expectation fails generated. Tests not run.');
    }

  }

  /**
   * Makes schema request and checks the response.
   *
   * @param string $format
   *   The described format.
   * @param string $entity_type_id
   *   Then entity type.
   * @param string|null $bundle_name
   *   The bundle name or NULL.
   */
  protected function requestSchema($format, $entity_type_id, $bundle_name = NULL) {
    $options = [
      'query' => [
        '_format' => 'schema_json',
        '_describes' => $format,
      ],
    ];
    $contents = $this->drupalGet("schemata/$entity_type_id/$bundle_name", $options);
    $this->assertSession()->statusCodeEquals(200);
    if (in_array($entity_type_id, ['node', 'taxonomy_term'])) {
      $this->assertFalse(empty($contents), "Content not empty for $format, $entity_type_id, $bundle_name");
      $file_name = __DIR__ . "/../../expectations/";
      if ($bundle_name) {
        $file_name .= "$entity_type_id.$bundle_name.$format.json";
      }
      else {
        $file_name .= "$entity_type_id.$format.json";
      }

      if ($this->generateExpectationFiles) {
        $this->saveExpectationFile($file_name, $contents);
        // Response assertion is not performed when generating expectation
        // files.
        return;
      }

      // Compare decoded json to so that failure will indicate which element is
      // incorrect.
      $expected = json_decode(file_get_contents($file_name), TRUE);
      $expected['id'] = str_replace('{base_url}', $this->baseUrl, $expected['id']);
      $decoded_response = json_decode($contents, TRUE);

      $this->assertEquals($expected, $decoded_response, "The response matches expected file: $file_name");
    }
  }

  /**
   * Saves an expectation file.
   *
   * @param string $file_name
   *   The file name of the expectation file.
   * @param string $contents
   *   The JSON response contents.
   *
   * @see \Drupal\Tests\schemata\Functional\RequestTest::$generateExpectationFiles
   */
  protected function saveExpectationFile($file_name, $contents) {
    $decoded_response = json_decode($contents, TRUE);
    // Replace the base url because will be different for different
    // environments.
    $decoded_response['id'] = str_replace($this->baseUrl, '{base_url}', $decoded_response['id']);
    $re_encode = json_encode($decoded_response, JSON_PRETTY_PRINT);
    file_put_contents($file_name, $re_encode);
  }

}
