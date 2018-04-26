<?php

namespace Drupal\jsonapi\Context;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service for accessing information about the current JSON API request.
 *
 * @internal
 */
class CurrentContext {

  /**
   * The JSON API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * The current JSON API resource type.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceType
   */
  protected $resourceType;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Creates a CurrentContext object.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The resource type repository.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(ResourceTypeRepositoryInterface $resource_type_repository, RequestStack $request_stack, RouteMatchInterface $route_match) {
    $this->resourceTypeRepository = $resource_type_repository;
    $this->requestStack = $request_stack;
    $this->routeMatch = $route_match;
  }

  /**
   * Gets the JSON API resource type for the current request.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType
   *   The JSON API resource type for the current request.
   */
  public function getResourceType() {
    if (!isset($this->resourceType)) {
      $route = $this->routeMatch->getRouteObject();
      $entity_type_id = $route->getRequirement('_entity_type');
      $bundle = $route->getRequirement('_bundle');
      $this->resourceType = $this->resourceTypeRepository
        ->get($entity_type_id, $bundle);
    }

    return $this->resourceType;
  }

  /**
   * Checks if the request is on a relationship.
   *
   * @return bool
   *   TRUE if the request is on a relationship. FALSE otherwise.
   */
  public function isOnRelationship() {
    return (bool) $this->routeMatch
      ->getRouteObject()
      ->getDefault('_on_relationship');
  }

  /**
   * Get a value by key from the _json_api_params route parameter.
   *
   * @param string $parameter_key
   *   The key by which to retrieve a route parameter.
   *
   * @return mixed
   *   The JSON API provided parameter.
   */
  public function getJsonApiParameter($parameter_key) {
    $params = $this
      ->requestStack
      ->getCurrentRequest()
      ->attributes
      ->get('_json_api_params');

    return isset($params[$parameter_key]) ? $params[$parameter_key] : NULL;
  }

  /**
   * Determines, whether the JSONAPI extension was requested.
   *
   * @todo Find a better place for such a JSONAPI derived information.
   *
   * @param string $extension_name
   *   The extension name.
   *
   * @return bool
   *   Returns TRUE, if the extension has been found.
   */
  public function hasExtension($extension_name) {
    return in_array($extension_name, $this->getExtensions());
  }

  /**
   * Returns a list of requested extensions.
   *
   * @return string[]
   *   The extension names.
   */
  public function getExtensions() {
    $content_type_header = $this
      ->requestStack
      ->getCurrentRequest()
      ->headers
      ->get('Content-Type');
    if (preg_match('/ext="([^"]+)"/i', $content_type_header, $match)) {
      $extensions = array_map('trim', explode(',', $match[1]));
      return $extensions;
    }
    return [];
  }

  /**
   * Reset the internal caches.
   */
  public function reset() {
    unset($this->resourceType);
  }

}
