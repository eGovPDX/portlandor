<?php

namespace Drupal\Tests\jsonapi\Unit\Routing;

use Drupal\jsonapi\Routing\JsonApiParamEnhancer;
use Drupal\jsonapi\Query\OffsetPage;
use Drupal\jsonapi\Query\Filter;
use Drupal\jsonapi\Query\Sort;
use Drupal\jsonapi\Routing\Routes;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Promise\ReturnPromise;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @coversDefaultClass \Drupal\jsonapi\Routing\JsonApiParamEnhancer
 * @group jsonapi
 * @group jsonapi_param_enhancer
 * @group legacy
 */
class JsonApiParamEnhancerTest extends UnitTestCase {

  /**
   * @covers ::applies
   */
  public function testApplies() {
    list($filter_normalizer, $sort_normalizer, $page_normalizer) = $this->getMockNormalizers();
    $object = new JsonApiParamEnhancer($filter_normalizer, $sort_normalizer, $page_normalizer);
    $route = $this->prophesize(Route::class);
    $route->getDefault(RouteObjectInterface::CONTROLLER_NAME)->will(new ReturnPromise([Routes::FRONT_CONTROLLER, 'lorem']));

    $this->assertTrue($object->applies($route->reveal()));
    $this->assertFalse($object->applies($route->reveal()));
  }

  /**
   * @covers ::enhance
   */
  public function testEnhanceFilter() {
    list($filter_normalizer, $sort_normalizer, $page_normalizer) = $this->getMockNormalizers();
    $object = new JsonApiParamEnhancer($filter_normalizer, $sort_normalizer, $page_normalizer);
    $request = $this->prophesize(Request::class);
    $query = $this->prophesize(ParameterBag::class);
    $query->get('filter')->willReturn(['filed1' => 'lorem']);
    $query->has(Argument::type('string'))->willReturn(FALSE);
    $query->has('filter')->willReturn(TRUE);
    $request->query = $query->reveal();

    $route = $this->prophesize(Route::class);
    $route->getRequirement('_entity_type')->willReturn('dolor');
    $route->getRequirement('_bundle')->willReturn('sit');
    $defaults = $object->enhance([
      RouteObjectInterface::ROUTE_OBJECT => $route->reveal(),
    ], $request->reveal());
    $this->assertInstanceOf(Filter::class, $defaults['_json_api_params']['filter']);
    $this->assertInstanceOf(OffsetPage::class, $defaults['_json_api_params']['page']);
    $this->assertTrue(empty($defaults['_json_api_params']['sort']));
  }

  /**
   * @covers ::enhance
   */
  public function testEnhancePage() {
    list($filter_normalizer, $sort_normalizer, $page_normalizer) = $this->getMockNormalizers();
    $object = new JsonApiParamEnhancer($filter_normalizer, $sort_normalizer, $page_normalizer);
    $request = $this->prophesize(Request::class);
    $query = $this->prophesize(ParameterBag::class);
    $query->get('page')->willReturn(['cursor' => 'lorem']);
    $query->has(Argument::type('string'))->willReturn(FALSE);
    $query->has('page')->willReturn(TRUE);
    $request->query = $query->reveal();

    $route = $this->prophesize(Route::class);
    $route->getRequirement('_entity_type')->willReturn('dolor');
    $route->getRequirement('_bundle')->willReturn('sit');
    $defaults = $object->enhance([
      RouteObjectInterface::ROUTE_OBJECT => $route->reveal(),
    ], $request->reveal());
    $this->assertInstanceOf(OffsetPage::class, $defaults['_json_api_params']['page']);
    $this->assertTrue(empty($defaults['_json_api_params']['filter']));
    $this->assertTrue(empty($defaults['_json_api_params']['sort']));
  }

  /**
   * @covers ::enhance
   */
  public function testEnhanceSort() {
    list($filter_normalizer, $sort_normalizer, $page_normalizer) = $this->getMockNormalizers();
    $object = new JsonApiParamEnhancer($filter_normalizer, $sort_normalizer, $page_normalizer);
    $request = $this->prophesize(Request::class);
    $query = $this->prophesize(ParameterBag::class);
    $query->get('sort')->willReturn('-lorem');
    $query->has(Argument::type('string'))->willReturn(FALSE);
    $query->has('sort')->willReturn(TRUE);
    $request->query = $query->reveal();

    $route = $this->prophesize(Route::class);
    $route->getRequirement('_entity_type')->willReturn('dolor');
    $route->getRequirement('_bundle')->willReturn('sit');
    $defaults = $object->enhance([
      RouteObjectInterface::ROUTE_OBJECT => $route->reveal(),
    ], $request->reveal());
    $this->assertInstanceOf(Sort::class, $defaults['_json_api_params']['sort']);
    $this->assertInstanceOf(OffsetPage::class, $defaults['_json_api_params']['page']);
    $this->assertTrue(empty($defaults['_json_api_params']['filter']));
  }

  /**
   * Builds mock normalizers.
   */
  public function getMockNormalizers() {
    $filter_normalizer = $this->prophesize(DenormalizerInterface::class);
    $filter_normalizer->denormalize(
      Argument::any(),
      Filter::class,
      Argument::any(),
      Argument::any()
    )->willReturn($this->prophesize(Filter::class)->reveal());

    $sort_normalizer = $this->prophesize(DenormalizerInterface::class);
    $sort_normalizer->denormalize(Argument::any(), Sort::class)->willReturn($this->prophesize(Sort::class)->reveal());

    $page_normalizer = $this->prophesize(DenormalizerInterface::class);
    $page_normalizer->denormalize(Argument::any(), OffsetPage::class)->willReturn($this->prophesize(OffsetPage::class)->reveal());

    return [
      $filter_normalizer->reveal(),
      $sort_normalizer->reveal(),
      $page_normalizer->reveal(),
    ];
  }

}
