<?php

namespace Drupal\Tests\jsonapi\Unit\Controller;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\jsonapi\LinkManager\LinkManager;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\Context\CurrentContext;
use Drupal\jsonapi\Controller\RequestHandler;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @coversDefaultClass \Drupal\jsonapi\Controller\RequestHandler
 * @group jsonapi
 *
 * @internal
 */
class RequestHandlerTest extends UnitTestCase {

  /**
   * @covers ::deserializeBody
   * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
   * @expectedExceptionMessageRegExp "There was an error un-serializing the data\..*"
   */
  public function testDeserializeBodyFail() {
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

    $request_handler = new RequestHandler(
      $serializer->reveal(),
      $current_context->reveal(),
      $this->prophesize(RendererInterface::class)->reveal(),
      $this->prophesize(ResourceTypeRepositoryInterface::class)->reveal(),
      $this->prophesize(EntityTypeManagerInterface::class)->reveal(),
      $this->prophesize(EntityFieldManagerInterface::class)->reveal(),
      $this->prophesize(FieldTypePluginManagerInterface::class)->reveal(),
      $this->prophesize(LinkManager::class)->reveal()
    );

    try {
      $request_handler->deserializeBody(
        $request->reveal(),
        'invalid'
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
