<?php

namespace Drupal\Tests\jsonapi\Unit\Context;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\jsonapi\Context\CurrentContext;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository;
use Drupal\jsonapi\Query\EntityConditionGroup;
use Drupal\jsonapi\Query\Filter;
use Drupal\jsonapi\Query\Sort;
use Drupal\jsonapi\Query\OffsetPage;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\jsonapi\Context\CurrentContext
 * @group jsonapi
 */
class CurrentContextTest extends UnitTestCase {

  /**
   * A mock for the current route.
   *
   * @var \Symfony\Component\Routing\Route
   */
  protected $currentRoute;

  /**
   * A mock for the JSON API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepository
   */
  protected $resourceTypeRepository;

  /**
   * A request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * @var \Drupal\Core\Routing\StackedRouteMatchInterface
   */
  protected $routeMatcher;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    // Create a mock for the entity field manager.
    $this->fieldManager = $this->prophesize(EntityFieldManagerInterface::class)->reveal();

    // Create a mock for the current route match.
    $this->currentRoute = new Route(
      '/jsonapi/articles',
      [],
      ['_entity_type' => 'node', '_bundle' => 'article']
    );

    // Create a mock for the ResourceTypeRepository service.
    $resource_type_repository_prophecy = $this->prophesize(ResourceTypeRepository::class);
    $resource_type_repository_prophecy->get('node', 'article')
      ->willReturn(new ResourceType('node', 'article', NodeInterface::class));
    $this->resourceTypeRepository = $resource_type_repository_prophecy->reveal();

    $this->requestStack = new RequestStack();
    $this->requestStack->push(new Request([], [], [
      '_json_api_params' => [
        'filter' => new Filter(new EntityConditionGroup('AND', [])),
        'sort' => new Sort([]),
        'page' => new OffsetPage(OffsetPage::DEFAULT_OFFSET, OffsetPage::SIZE_MAX),
        // 'include' => new IncludeParam([]),
        // 'fields' => new Fields([]),.
      ],
      RouteObjectInterface::ROUTE_OBJECT => $this->currentRoute,
    ]));

    $this->routeMatcher = new CurrentRouteMatch($this->requestStack);
  }

  /**
   * @covers ::getResourceType
   */
  public function testGetResourceType() {
    $request_context = new CurrentContext($this->resourceTypeRepository, $this->requestStack, $this->routeMatcher);

    $this->assertEquals(
      $this->resourceTypeRepository->get('node', 'article'),
      $request_context->getResourceType()
    );
  }

  /**
   * @covers ::getJsonApiParameter
   */
  public function testGetJsonApiParameter() {
    $request_context = new CurrentContext($this->resourceTypeRepository, $this->requestStack, $this->routeMatcher);

    $actual = $request_context->getJsonApiParameter('sort');

    $this->assertTrue($actual instanceof Sort);
  }

  /**
   * @covers ::hasExtension
   */
  public function testHasExtensionWithExistingExtension() {
    $request = new Request();
    $request->headers->set('Content-Type', 'application/vnd.api+json; ext="ext1,ext2"');
    $this->requestStack->push($request);
    $request_context = new CurrentContext($this->resourceTypeRepository, $this->requestStack, $this->routeMatcher);

    $this->assertTrue($request_context->hasExtension('ext1'));
    $this->assertTrue($request_context->hasExtension('ext2'));
  }

  /**
   * @covers ::getExtensions
   */
  public function testGetExtensions() {
    $request = new Request();
    $request->headers->set('Content-Type', 'application/vnd.api+json; ext="ext1,ext2"');
    $this->requestStack->push($request);
    $request_context = new CurrentContext($this->resourceTypeRepository, $this->requestStack, $this->routeMatcher);

    $this->assertEquals(['ext1', 'ext2'], $request_context->getExtensions());
  }

  /**
   * @covers ::hasExtension
   */
  public function testHasExtensionWithNotExistingExtension() {
    $request = new Request();
    $request->headers->set('Content-Type', 'application/vnd.api+json;');
    $this->requestStack->push($request);
    $request_context = new CurrentContext($this->resourceTypeRepository, $this->requestStack, $this->routeMatcher);
    $this->assertFalse($request_context->hasExtension('ext1'));
    $this->assertFalse($request_context->hasExtension('ext2'));
  }

}
