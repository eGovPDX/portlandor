<?php

namespace Drupal\Tests\openapi\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\rest\Entity\RestResourceConfig;
use Drupal\rest\RestResourceConfigInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests requests OpenAPI routes.
 *
 * @group OpenAPI
 */
class RequestTest extends BrowserTestBase {

  /**
   * Set to TRUE to run this test to generate expectation files.
   *
   * The test will be marked as a fail when generating test files.
   */
  const GENERATE_EXPECTATION_FILES = FALSE;

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
    'openapi',
    'rest',
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

    $enable_entity_types = [
      'node' => ['GET', 'POST', 'PATCH', 'DELETE'],
      'user' => ['GET'],
      'taxonomy_term' => ['GET', 'POST', 'PATCH', 'DELETE'],
      'taxonomy_vocabulary' => ['GET'],
    ];
    foreach ($enable_entity_types as $entity_type_id => $methods) {
      foreach ($methods as $method) {
        $this->enableRestService("entity:$entity_type_id", $method);
      }
    }
    $this->container->get('router.builder')->rebuild();
    $this->drupalLogin($this->drupalCreateUser([
      'access openapi api docs',
      'access content',
    ]));

    // @todo Add JSON API to $modules
    //   Currently this will not work because the new bundles are not picked up
    //   in \Drupal\jsonapi\Routing\Routes::routes().
    $this->container->get('module_installer')->install(['jsonapi']);
  }

  /**
   * Tests OpenAPI requests.
   */
  public function testRequests() {

    foreach (['rest', 'jsonapi'] as $api_module) {
      // Make request with no options to produce full result.
      $this->requestOpenApiJson($api_module);
      $option_sets = [
        'node' => [
          'entity_type_id' => 'node',
        ],
        'node_camelids' => [
          'entity_type_id' => 'node',
          'bundle_name' => 'camelids',
        ],
        'taxonomy_term' => [
          'entity_type_id' => 'taxonomy_term',
        ],
        'taxonomy_term_camelids' => [
          'entity_type_id' => 'taxonomy_term',
          'bundle_name' => 'camelids',
        ],
        'user' => [
          'entity_type_id' => 'user',
        ],
      ];
      // Test the output using various options.
      foreach ($option_sets as $options) {
        $this->requestOpenApiJson($api_module, $options);
      }
    }

    if (static::GENERATE_EXPECTATION_FILES) {
      $this->fail('Expectation fails generated. Change \Drupal\Tests\openapi\Functional\RequestTest::GENERATE_EXPECTATION_FILES to FALSE to run tests.');
    }

  }

  /**
   * Makes OpenAPI request and checks the response.
   *
   * @param string $api_module
   *   The API module being tested. Either 'rest' or 'jsonapi'.
   * @param array $options
   *   The query options for generating the OpenAPI output.
   */
  protected function requestOpenApiJson($api_module, array $options = []) {
    $get_options = [
      'query' => [
        '_format' => 'json',
        'options' => $options,
      ],
    ];
    $contents = $this->drupalGet("openapi/$api_module", $get_options);
    $this->assertSession()->statusCodeEquals(200);

    $file_name = __DIR__ . "/../../expectations/$api_module";
    if ($options) {
      $file_name .= "." . implode('.', $options);
    }
    $file_name .= '.json';

    if (static::GENERATE_EXPECTATION_FILES) {
      $this->saveExpectationFile($file_name, $contents);
      // Response assertion is not performed when generating expectation
      // files.
      return;
    }

    // Compare decoded json to so that failure will indicate which element is
    // incorrect.
    $expected = json_decode(file_get_contents($file_name), TRUE);
    $this->nestedKsort($expected);
    $host = str_replace('/' . $this->getBasePath(), '', $this->baseUrl);
    $host = str_replace('http://', '', $host);
    $expected['host'] = str_replace('{host}', $host, $expected['host']);
    $expected['basePath'] = str_replace('{base_path}', $this->getBasePath(), $expected['basePath']);
    $decoded_response = json_decode($contents, TRUE);
    $this->nestedKsort($decoded_response);

    $this->assertEquals($expected, $decoded_response, "The response matches expected file: $file_name");
  }

  /**
   * Saves an expectation file.
   *
   * @param string $file_name
   *   The file name of the expectation file.
   * @param string $contents
   *   The JSON response contents.
   *
   * @see \Drupal\Tests\openapi\Functional\RequestTest::GENERATE_EXPECTATION_FILES
   */
  protected function saveExpectationFile($file_name, $contents) {
    $decoded_response = json_decode($contents, TRUE);
    // Replace the base url because will be different for different
    // environments.
    $decoded_response['host'] = '{host}';

    $decoded_response['basePath'] = str_replace($this->getBasePath(), '{base_path}', $decoded_response['basePath']);
    $re_encode = json_encode($decoded_response, JSON_PRETTY_PRINT);
    file_put_contents($file_name, $re_encode);
  }

  /**
   * Enables the REST service interface for a specific entity type.
   *
   * @param string|false $resource_type
   *   The resource type that should get REST API enabled or FALSE to disable all
   *   resource types.
   * @param string $method
   *   The HTTP method to enable, e.g. GET, POST etc.
   * @param string|array $format
   *   (Optional) The serialization format, e.g. hal_json, or a list of formats.
   * @param array $auth
   *   (Optional) The list of valid authentication methods.
   */
  protected function enableRestService($resource_type, $method = 'GET', $format = 'json', array $auth = ['basic_auth']) {
    if ($resource_type) {
      // Enable REST API for this entity type.
      $resource_config_id = str_replace(':', '.', $resource_type);
      // get entity by id
      /** @var \Drupal\rest\RestResourceConfigInterface $resource_config */
      $resource_config = RestResourceConfig::load($resource_config_id);
      if (!$resource_config) {
        $resource_config = RestResourceConfig::create([
          'id' => $resource_config_id,
          'granularity' => RestResourceConfigInterface::METHOD_GRANULARITY,
          'configuration' => [],
        ]);
      }
      $configuration = $resource_config->get('configuration');

      if (is_array($format)) {
        for ($i = 0; $i < count($format); $i++) {
          $configuration[$method]['supported_formats'][] = $format[$i];
        }
      }
      else {

        $configuration[$method]['supported_formats'][] = $format;
      }

      foreach ($auth as $auth_provider) {
        $configuration[$method]['supported_auth'][] = $auth_provider;
      }

      $resource_config->set('configuration', $configuration);
      $resource_config->save();
    }
    else {
      foreach (RestResourceConfig::loadMultiple() as $resource_config) {
        $resource_config->delete();
      }
    }
  }

  /**
   * Gets the base path to be used in OpenAPI.
   *
   * @return string
   *   The base path.
   */
  protected function getBasePath() {
    $base = str_replace('http://', '', $this->baseUrl);
    $base_parts = explode('/', $base);
    array_shift($base_parts);
    $base = implode('/', $base_parts);
    return $base;
  }

  /**
   * Sorts a nested array with ksort().
   *
   * @param array $array
   *   The nested array to sort.
   */
  protected function nestedKsort(array &$array) {
    if ($this->isAssocArray($array)) {
      ksort($array);
    }
    else {
      usort($array, function ($a, $b) {
        if (isset($a['name']) && isset($b['name'])) {
          return strcmp($a['name'], $b['name']);
        }
        return -1;
      });
    }

    foreach ($array as &$item) {
      if (is_array($item)) {
        $this->nestedKsort($item);
      }
    }
  }

  protected function isAssocArray(array $arr) {
    if (array() === $arr) {
      return FALSE;
    }
    return array_keys($arr) !== range(0, count($arr) - 1);
  }

}
