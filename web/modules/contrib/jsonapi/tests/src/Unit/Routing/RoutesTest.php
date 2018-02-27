<?php

namespace Drupal\Tests\jsonapi\Unit\Routing;

use Drupal\Core\Authentication\AuthenticationCollectorInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository;
use Drupal\jsonapi\Routing\Routes;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\jsonapi\Routing\Routes
 * @group jsonapi
 */
class RoutesTest extends UnitTestCase {

  /**
   * List of routes objects for the different scenarios.
   *
   * @var \Drupal\jsonapi\Routing\Routes[]
   */
  protected $routes;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $resource_type_repository = $this->prophesize(ResourceTypeRepository::class);
    $resource_type_repository->all()->willReturn([new ResourceType('entity_type_1', 'bundle_1_1', EntityInterface::class)]);
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('jsonapi.resource_type.repository')->willReturn($resource_type_repository->reveal());
    $auth_collector = $this->prophesize(AuthenticationCollectorInterface::class);
    $auth_collector->getSortedProviders()->willReturn([
      'lorem' => [],
      'ipsum' => [],
    ]);
    $container->get('authentication_collector')->willReturn($auth_collector->reveal());

    $this->routes['ok'] = Routes::create($container->reveal());
  }

  /**
   * @covers ::routes
   */
  public function testRoutesCollection() {
    // Get the route collection and start making assertions.
    $routes = $this->routes['ok']->routes();

    // Make sure that there are 4 routes for each resource.
    $this->assertEquals(4, $routes->count());

    $iterator = $routes->getIterator();
    // Check the collection route.
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $iterator->offsetGet('jsonapi.entity_type_1--bundle_1_1.collection');
    $this->assertSame('/jsonapi/entity_type_1/bundle_1_1', $route->getPath());
    $this->assertSame('entity_type_1', $route->getRequirement('_entity_type'));
    $this->assertSame('bundle_1_1', $route->getRequirement('_bundle'));
    $this->assertSame(['lorem', 'ipsum'], $route->getOption('_auth'));
    $this->assertEquals(['GET', 'POST'], $route->getMethods());
    $this->assertSame(Routes::FRONT_CONTROLLER, $route->getDefault(RouteObjectInterface::CONTROLLER_NAME));
    $this->assertSame('Drupal\jsonapi\Resource\JsonApiDocumentTopLevel', $route->getOption('serialization_class'));
  }

  /**
   * @covers ::routes
   */
  public function testRoutesIndividual() {
    // Get the route collection and start making assertions.
    $iterator = $this->routes['ok']->routes()->getIterator();

    // Check the individual route.
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $iterator->offsetGet('jsonapi.entity_type_1--bundle_1_1.individual');
    $this->assertSame('/jsonapi/entity_type_1/bundle_1_1/{entity_type_1}', $route->getPath());
    $this->assertSame('entity_type_1', $route->getRequirement('_entity_type'));
    $this->assertSame('bundle_1_1', $route->getRequirement('_bundle'));
    $this->assertEquals(['GET', 'PATCH', 'DELETE'], $route->getMethods());
    $this->assertSame(Routes::FRONT_CONTROLLER, $route->getDefault(RouteObjectInterface::CONTROLLER_NAME));
    $this->assertSame('Drupal\jsonapi\Resource\JsonApiDocumentTopLevel', $route->getOption('serialization_class'));
    $this->assertSame(['lorem', 'ipsum'], $route->getOption('_auth'));
    $this->assertEquals(['entity_type_1' => ['type' => 'entity:entity_type_1']], $route->getOption('parameters'));
  }

  /**
   * @covers ::routes
   */
  public function testRoutesRelated() {
    // Get the route collection and start making assertions.
    $iterator = $this->routes['ok']->routes()->getIterator();

    // Check the related route.
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $iterator->offsetGet('jsonapi.entity_type_1--bundle_1_1.related');
    $this->assertSame('/jsonapi/entity_type_1/bundle_1_1/{entity_type_1}/{related}', $route->getPath());
    $this->assertSame('entity_type_1', $route->getRequirement('_entity_type'));
    $this->assertSame('bundle_1_1', $route->getRequirement('_bundle'));
    $this->assertEquals(['GET'], $route->getMethods());
    $this->assertSame(Routes::FRONT_CONTROLLER, $route->getDefault(RouteObjectInterface::CONTROLLER_NAME));
    $this->assertSame(['lorem', 'ipsum'], $route->getOption('_auth'));
    $this->assertEquals(['entity_type_1' => ['type' => 'entity:entity_type_1']], $route->getOption('parameters'));
  }

  /**
   * @covers ::routes
   */
  public function testRoutesRelationships() {
    // Get the route collection and start making assertions.
    $iterator = $this->routes['ok']->routes()->getIterator();

    // Check the relationships route.
    /** @var \Symfony\Component\Routing\Route $route */
    $route = $iterator->offsetGet('jsonapi.entity_type_1--bundle_1_1.relationship');
    $this->assertSame('/jsonapi/entity_type_1/bundle_1_1/{entity_type_1}/relationships/{related}', $route->getPath());
    $this->assertSame('entity_type_1', $route->getRequirement('_entity_type'));
    $this->assertSame('bundle_1_1', $route->getRequirement('_bundle'));
    $this->assertEquals(['GET', 'POST', 'PATCH', 'DELETE'], $route->getMethods());
    $this->assertSame(Routes::FRONT_CONTROLLER, $route->getDefault(RouteObjectInterface::CONTROLLER_NAME));
    $this->assertSame(['lorem', 'ipsum'], $route->getOption('_auth'));
    $this->assertEquals(['entity_type_1' => ['type' => 'entity:entity_type_1']], $route->getOption('parameters'));
    $this->assertSame('Drupal\Core\Field\EntityReferenceFieldItemList', $route->getOption('serialization_class'));
  }

}
