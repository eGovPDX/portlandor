<?php

namespace Drupal\jsonapi\Routing;

use Drupal\Core\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;

/**
 * Ensures the loaded entity matches the requested resource type.
 *
 * @internal
 */
class RouteEnhancer implements RouteEnhancerInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return (bool) $route->getRequirement('_bundle') && (bool) $route->getRequirement('_entity_type');
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    $route = $defaults[RouteObjectInterface::ROUTE_OBJECT];
    $entity_type = $route->getRequirement('_entity_type');
    if (!isset($defaults[$entity_type]) || !($entity = $defaults[$entity_type])) {
      return $defaults;
    }
    $retrieved_bundle = $entity->bundle();
    $configured_bundle = $route->getRequirement('_bundle');
    if ($retrieved_bundle != $configured_bundle) {
      // If the bundle in the loaded entity does not match the bundle in the
      // route (which is set based on the corresponding ResourceType), then
      // throw an exception.
      throw new NotFoundHttpException(sprintf('The loaded entity bundle (%s) does not match the configured resource (%s).', $retrieved_bundle, $configured_bundle));
    }
    return $defaults;
  }

}
