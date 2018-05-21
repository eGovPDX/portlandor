<?php

namespace Drupal\Tests\jsonapi\Unit\EventSubscriber;

use JsonSchema\Validator;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\jsonapi\EventSubscriber\ResourceResponseSubscriber;
use Drupal\rest\ResourceResponse;
use Drupal\schemata\SchemaFactory;
use Drupal\schemata\Encoder\JsonSchemaEncoder;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Serializer\Serializer;

/**
 * @coversDefaultClass \Drupal\jsonapi\EventSubscriber\ResourceResponseSubscriber
 * @group jsonapi
 *
 * @internal
 */
class ResourceResponseSubscriberTest extends UnitTestCase {

  /**
   * The subscriber under test.
   *
   * @var \Drupal\jsonapi\EventSubscriber\ResourceResponseSubscriber
   */
  protected $subscriber;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Check that the validation class is available.
    if (!class_exists("\\JsonSchema\\Validator")) {
      $this->fail('The JSON Schema validator is missing. You can install it with `composer require justinrainbow/json-schema`.');
    }

    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $module = $this->prophesize(Extension::class);
    $module_path = dirname(dirname(dirname(dirname(__DIR__))));
    $module->getPath()->willReturn($module_path);
    $module_handler->getModule('jsonapi')->willReturn($module->reveal());
    $subscriber = new ResourceResponseSubscriber(
      new Serializer([], [new JsonSchemaEncoder()]),
      $this->prophesize(RendererInterface::class)->reveal(),
      $this->prophesize(LoggerInterface::class)->reveal(),
      $module_handler->reveal(),
      ''
    );
    $subscriber->setValidator();
    $this->subscriber = $subscriber;
  }

  /**
   * @covers ::doValidateResponse
   */
  public function testDoValidateResponse() {
    $request = $this->createRequest(
      'jsonapi.node--article.individual',
      '/jsonapi/node/article/{node}',
      ['_entity_type' => 'node', '_bundle' => 'article']
    );

    $response = $this->createResponse('{"data":null}');

    // Capture the default assert settings.
    $zend_assertions_default = ini_get('zend.assertions');
    $assert_active_default = assert_options(ASSERT_ACTIVE);

    // The validator *should* be called when asserts are active.
    $validator = $this->prophesize(Validator::class);
    $validator->check(Argument::any(), Argument::any())->shouldBeCalled('Validation should be run when asserts are active.');
    $validator->isValid()->willReturn(TRUE);
    $this->subscriber->setValidator($validator->reveal());

    // Ensure asset is active.
    ini_set('zend.assertions', 1);
    assert_options(ASSERT_ACTIVE, 1);
    $this->subscriber->doValidateResponse($response, $request);

    // The validator should *not* be called when asserts are inactive.
    $validator = $this->prophesize(Validator::class);
    $validator->check(Argument::any(), Argument::any())->shouldNotBeCalled('Validation should not be run when asserts are not active.');
    $this->subscriber->setValidator($validator->reveal());

    // Ensure asset is inactive.
    ini_set('zend.assertions', 0);
    assert_options(ASSERT_ACTIVE, 0);
    $this->subscriber->doValidateResponse($response, $request);

    // Reset the original assert values.
    ini_set('zend.assertions', $zend_assertions_default);
    assert_options(ASSERT_ACTIVE, $assert_active_default);
  }

  /**
   * @covers ::onResponse
   */
  public function testValidateResponseSchemata() {
    $request = $this->createRequest(
      'jsonapi.node--article.individual',
      '/jsonapi/node/article/{node}',
      ['_entity_type' => 'node', '_bundle' => 'article']
    );

    $response = $this->createResponse('{"data":null}');

    // The validator should be called *once* if schemata is *not* installed.
    $validator = $this->prophesize(Validator::class);
    $validator->check(Argument::any(), Argument::any())->shouldBeCalledTimes(1);
    $validator->isValid()->willReturn(TRUE);
    $this->subscriber->setValidator($validator->reveal());

    // Run validations.
    $this->subscriber->doValidateResponse($response, $request);

    // The validator should be called *twice* if schemata is installed.
    $validator = $this->prophesize(Validator::class);
    $validator->check(Argument::any(), Argument::any())->shouldBeCalledTimes(2);
    $validator->isValid()->willReturn(TRUE);
    $this->subscriber->setValidator($validator->reveal());

    // Make the schemata factory available.
    $schema_factory = $this->prophesize(SchemaFactory::class);
    $schema_factory->create('node', 'article')->willReturn('{}');
    $this->subscriber->setSchemaFactory($schema_factory->reveal());

    // Run validations.
    $this->subscriber->doValidateResponse($response, $request);

    // The validator resource specific schema should *not* be validated on
    // 'related' routes.
    $request = $this->createRequest(
      'jsonapi.node--article.related',
      '/jsonapi/node/article/{node}/foo',
      ['_entity_type' => 'node', '_bundle' => 'article']
    );

    // Since only the generic schema should be validated, the validator should
    // only be called once.
    $validator = $this->prophesize(Validator::class);
    $validator->check(Argument::any(), Argument::any())->shouldBeCalledTimes(1);
    $validator->isValid()->willReturn(TRUE);
    $this->subscriber->setValidator($validator->reveal());

    // Run validations.
    $this->subscriber->doValidateResponse($response, $request);

    // The validator resource specific schema should *not* be validated on
    // 'relationship' routes.
    $request = $this->createRequest(
      'jsonapi.node--article.relationship',
      '/jsonapi/node/article/{node}/relationships/foo',
      ['_entity_type' => 'node', '_bundle' => 'article']
    );

    // Since only the generic schema should be validated, the validator should
    // only be called once.
    $validator = $this->prophesize(Validator::class);
    $validator->check(Argument::any(), Argument::any())->shouldBeCalledTimes(1);
    $validator->isValid()->willReturn(TRUE);
    $this->subscriber->setValidator($validator->reveal());

    // Run validations.
    $this->subscriber->doValidateResponse($response, $request);
  }

  /**
   * @covers ::validateResponse
   * @dataProvider validateResponseProvider
   */
  public function testValidateResponse($request, $response, $expected, $description) {
    // Expose protected ResourceResponseSubscriber::validateResponse() method.
    $object = new \ReflectionObject($this->subscriber);
    $method = $object->getMethod('validateResponse');
    $method->setAccessible(TRUE);

    $this->assertSame($expected, $method->invoke($this->subscriber, $response, $request), $description);
  }

  /**
   * Provides test cases for testValidateResponse.
   *
   * @return array
   *   An array of test cases.
   */
  public function validateResponseProvider() {
    $defaults = [
      'route_name' => 'jsonapi.node--article.individual',
      'route' => '/jsonapi/node/article/{node}',
      'requirements' => ['_entity_type' => 'node', '_bundle' => 'article'],
    ];

    $test_data = [
      // Test validation success.
      [
        'json' => <<<'EOD'
{
  "data": {
    "type": "node--article",
    "id": "4f342419-e668-4b76-9f87-7ce20c436169",
    "attributes": {
      "nid": "1",
      "uuid": "4f342419-e668-4b76-9f87-7ce20c436169"
    }
  }
}
EOD
        ,
        'expected' => TRUE,
        'description' => 'Response validation flagged a valid response.',
      ],
      // Test validation failure: no "type" in "data".
      [
        'json' => <<<'EOD'
{
  "data": {
    "id": "4f342419-e668-4b76-9f87-7ce20c436169",
    "attributes": {
      "nid": "1",
      "uuid": "4f342419-e668-4b76-9f87-7ce20c436169"
    }
  }
}
EOD
        ,
        'expected' => FALSE,
        'description' => 'Response validation failed to flag an invalid response.',
      ],
      // Test validation failure: "errors" at the root level.
      [
        'json' => <<<'EOD'
{
  "data": {
  "type": "node--article",
    "id": "4f342419-e668-4b76-9f87-7ce20c436169",
    "attributes": {
    "nid": "1",
      "uuid": "4f342419-e668-4b76-9f87-7ce20c436169"
    }
  },
  "errors": [{}]
}
EOD
        ,
        'expected' => FALSE,
        'description' => 'Response validation failed to flag an invalid response.',
      ],
      // Test validation of an empty response passes.
      [
        'json' => NULL,
        'expected' => TRUE,
        'description' => 'Response validation flagged a valid empty response.',
      ],
      // Test validation fails on empty object.
      [
        'json' => '{}',
        'expected' => FALSE,
        'description' => 'Response validation flags empty array as invalid.',
      ],
    ];

    $test_cases = array_map(function ($input) use ($defaults) {
      list($json, $expected, $description, $route_name, $route, $requirements) = array_values($input + $defaults);
      return [
        $this->createRequest($route_name, $route, $requirements),
        $this->createResponse($json),
        $expected,
        $description,
      ];
    }, $test_data);

    return $test_cases;
  }

  /**
   * Helper method to create a request object.
   *
   * @param string $route_name
   *   The route name with which to construct a request.
   * @param string $route
   *   The route object with which to construct a request.
   * @param array $requirements
   *   The route requirements.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The mock request object.
   */
  protected function createRequest($route_name, $route, array $requirements = []) {
    $request = new Request();
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, $route_name);
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, (new Route($route))->setRequirements($requirements));
    return $request;
  }

  /**
   * Helper method to create a resource response from arbitrary JSON.
   *
   * @param string|null $json
   *   The JSON with which to create a mock response.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The mock response object.
   */
  protected function createResponse($json = NULL) {
    $response = new ResourceResponse();
    if ($json) {
      $response->setContent($json);
    }
    return $response;
  }

}
