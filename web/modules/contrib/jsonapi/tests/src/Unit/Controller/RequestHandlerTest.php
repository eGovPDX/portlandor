<?php

namespace Drupal\Tests\jsonapi\Unit\Controller;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\Context\CurrentContext;
use Drupal\jsonapi\Controller\RequestHandler;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @coversDefaultClass \Drupal\jsonapi\Controller\RequestHandler
 * @group jsonapi
 */
class RequestHandlerTest extends UnitTestCase {

  /**
   * @covers ::deserializeBody
   * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
   * @expectedExceptionMessageRegExp "There was an error un-serializing the data\..*"
   */
  public function testDeserializeBodyFail() {
    $entity_storage = $this->prophesize(EntityStorageInterface::class);
    $request_handler = new RequestHandler($entity_storage->reveal());
    $request = $this->prophesize(Request::class);
    $request->getContentType()->willReturn(NULL);
    $request->getContent()->willReturn('this is not used');
    $request->isMethodCacheable()->willReturn(FALSE);
    $request->getMethod()->willReturn(NULL);
    $request->get(Argument::any())->willReturn(NULL);
    $request->getMimeType(Argument::any())->willReturn(NULL);
    $serializer = $this->prophesize(SerializerInterface::class);
    $serializer->deserialize(Argument::type('string'), Argument::type('string'), Argument::any(), Argument::type('array'))
      ->willThrow(new UnexpectedValueException('Foo'));
    $serializer->serialize(Argument::any(), Argument::any(), Argument::any())
      ->willReturn('{"errors":[{"status":422,"message":"Foo"}]}');
    $current_context = $this->prophesize(CurrentContext::class);
    $current_context->getResourceType()
      ->willReturn(new ResourceType($this->randomMachineName(), $this->randomMachineName(), NULL));
    try {
      $request_handler->deserializeBody(
        $request->reveal(),
        $serializer->reveal(),
        'invalid',
        $current_context->reveal()
      );
      $this->fail('Expected exception.');
    }
    catch (HttpException $e) {
      $this->assertEquals(422, $e->getStatusCode());
      // Re-throw the exception so the test runner can catch it.
      throw $e;
    }
  }

}
