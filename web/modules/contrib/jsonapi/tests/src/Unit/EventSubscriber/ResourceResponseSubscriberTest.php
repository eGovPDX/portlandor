<?php

namespace Drupal\Tests\jsonapi\Unit\EventSubscriber;

use Drupal\Core\Render\RendererInterface;
use Drupal\jsonapi\EventSubscriber\ResourceResponseSubscriber;
use Drupal\rest\ResourceResponse;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * @coversDefaultClass \Drupal\jsonapi\EventSubscriber\ResourceResponseSubscriber
 * @group jsonapi
 */
class ResourceResponseSubscriberTest extends UnitTestCase {

  /**
   * @covers ::validateResponse
   */
  public function testValidateResponse() {
    $resource_response_subscriber = new ResourceResponseSubscriber(
      $this->prophesize(Serializer::class)->reveal(),
      $this->prophesize(RendererInterface::class)->reveal(),
      $this->prophesize(LoggerInterface::class)->reveal()
    );

    // Check that the validation class is enabled.
    $this->assertTrue(
      class_exists("\\JsonSchema\\Validator"),
      'The JSON Schema validator is not present. Please make sure to install it using composer.'
    );

    // Expose protected ResourceResponseSubscriber::validateResponse() method.
    $object = new \ReflectionObject($resource_response_subscriber);
    $validate_response = $object->getMethod('validateResponse');
    $validate_response->setAccessible(TRUE);

    // Test validation failure: no "type" in "data".
    $json = <<<'EOD'
{
  "data": {
    "id": "4f342419-e668-4b76-9f87-7ce20c436169",
    "attributes": {
      "nid": "1",
      "uuid": "4f342419-e668-4b76-9f87-7ce20c436169"
    }
  }
}
EOD;
    $response = new ResourceResponse();
    $response->setContent($json);
    $this->assertFalse(
      $validate_response->invoke($resource_response_subscriber, $response),
      'Response validation failed to flag an invalid response.'
    );

    // Test validation failure: no "data" and "errors" at the root level.
    $json = <<<'EOD'
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
EOD;
    $response = new ResourceResponse();
    $response->setContent($json);
    $this->assertFalse(
      $validate_response->invoke($resource_response_subscriber, $response),
      'Response validation failed to flag an invalid response.'
    );

    // Test validation success.
    $json = <<<'EOD'
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
EOD;
    $response->setContent($json);
    $this->assertTrue(
      $validate_response->invoke($resource_response_subscriber, $response),
      'Response validation flagged a valid response.'
    );

    // Test validation of an empty response passes.
    $response = new ResourceResponse();
    $this->assertTrue(
      $validate_response->invoke($resource_response_subscriber, $response),
      'Response validation flagged a valid empty response.'
    );

  }

}
