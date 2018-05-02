<?php

namespace Drupal\jsonapi\Controller;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\jsonapi\Context\CurrentContext;
use Drupal\jsonapi\LinkManager\LinkManager;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Acts as intermediate request forwarder for resource plugins.
 *
 * @internal
 */
class RequestHandler {

  /**
   * The JSON API serializer.
   *
   * @var \Drupal\jsonapi\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The current JSON API context.
   *
   * @var \Drupal\jsonapi\Context\CurrentContext
   */
  protected $currentContext;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The JSON API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * The field type manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * The JSON API link manager.
   *
   * @var \Drupal\jsonapi\LinkManager\LinkManager
   */
  protected $linkManager;

  protected static $requiredCacheContexts = ['user.permissions'];

  /**
   * Creates a new RequestHandler instance.
   *
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The JSON API serializer.
   * @param \Drupal\jsonapi\Context\CurrentContext $current_context
   *   The current JSON API context.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The resource type repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type manager.
   * @param \Drupal\jsonapi\LinkManager\LinkManager $link_manager
   *   The JSON API link manager.
   */
  public function __construct(SerializerInterface $serializer, CurrentContext $current_context, RendererInterface $renderer, ResourceTypeRepositoryInterface $resource_type_repository, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $field_manager, FieldTypePluginManagerInterface $field_type_manager, LinkManager $link_manager) {
    $this->serializer = $serializer;
    $this->currentContext = $current_context;
    $this->renderer = $renderer;
    $this->resourceTypeRepository = $resource_type_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldManager = $field_manager;
    $this->fieldTypeManager = $field_type_manager;
    $this->linkManager = $link_manager;
  }

  /**
   * Handles a web API request.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request object.
   *
   * @return \Drupal\Core\Cache\CacheableResponseInterface
   *   The response object.
   */
  public function handle(RouteMatchInterface $route_match, Request $request) {
    $method = strtolower($request->getMethod());
    $route = $route_match->getRouteObject();

    // Deserialize incoming data if available.
    $this->currentContext->reset();
    $unserialized = $this->deserializeBody($request, $route->getOption('serialization_class'));
    if ($unserialized instanceof Response && !$unserialized->isSuccessful()) {
      return $unserialized;
    }

    // Determine the request parameters that should be passed to the resource
    // plugin.
    $route_parameters = $route_match->getParameters();
    $parameters = [];

    // Filter out all internal parameters starting with "_".
    foreach ($route_parameters as $key => $parameter) {
      if ($key{0} !== '_') {
        $parameters[] = $parameter;
      }
    }

    // Invoke the operation on the resource plugin.
    $action = $this->action($route_match, $method);
    $resource = $this->resourceFactory($route);

    // Only add the unserialized data if there is something there.
    $extra_parameters = $unserialized ? [$unserialized, $request] : [$request];

    // Execute the request in context so the cacheable metadata from the entity
    // grants system is caught and added to the response. This is surfaced when
    // executing the underlying entity query.
    $context = new RenderContext();
    $response = $this->renderer
      ->executeInRenderContext($context, function () use ($resource, $action, $parameters, $extra_parameters) {
        return call_user_func_array([$resource, $action], array_merge($parameters, $extra_parameters));
      });
    $response->getCacheableMetadata()->addCacheContexts(static::$requiredCacheContexts);
    if (!$context->isEmpty()) {
      $response->addCacheableDependency($context->pop());
    }

    return $response;
  }

  /**
   * Deserializes the sent data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $serialization_class
   *   The class the input data needs to deserialize into.
   *
   * @return mixed
   *   The deserialized data or a Response object in case of error.
   */
  public function deserializeBody(Request $request, $serialization_class) {
    $received = $request->getContent();
    if (empty($received) || $request->isMethodCacheable()) {
      return NULL;
    }
    $resource_type = $this->currentContext->getResourceType();
    $field_related = $resource_type->getInternalName($request->get('related'));
    try {
      return $this->serializer->deserialize($received, $serialization_class, 'api_json', [
        'related' => $field_related,
        'target_entity' => $request->get($this->currentContext->getResourceType()->getEntityTypeId()),
        'resource_type' => $resource_type,
      ]);
    }
    catch (UnexpectedValueException $e) {
      throw new UnprocessableEntityHttpException(
        sprintf('There was an error un-serializing the data. Message: %s', $e->getMessage()),
        $e
      );
    }
  }

  /**
   * Gets the method to execute in the entity resource.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param string $method
   *   The lowercase HTTP method.
   *
   * @return string
   *   The method to execute in the EntityResource.
   */
  protected function action(RouteMatchInterface $route_match, $method) {
    $on_relationship = ($route_match->getRouteObject()->getDefault('_on_relationship'));
    switch ($method) {
      case 'head':
      case 'get':
        if ($on_relationship) {
          return 'getRelationship';
        }
        elseif ($route_match->getParameter('related')) {
          return 'getRelated';
        }
        return $this->getEntity($route_match) ? 'getIndividual' : 'getCollection';

      case 'post':
        return ($on_relationship) ? 'createRelationship' : 'createIndividual';

      case 'patch':
        return ($on_relationship) ? 'patchRelationship' : 'patchIndividual';

      case 'delete':
        return ($on_relationship) ? 'deleteRelationship' : 'deleteIndividual';
    }
  }

  /**
   * Gets the entity for the operation.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The matched route.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The upcasted entity.
   */
  protected function getEntity(RouteMatchInterface $route_match) {
    $route = $route_match->getRouteObject();
    return $route_match->getParameter($route->getRequirement('_entity_type'));
  }

  /**
   * Get the resource.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The matched route.
   *
   * @return \Drupal\jsonapi\Controller\EntityResource
   *   The instantiated resource.
   */
  protected function resourceFactory(Route $route) {
    $resource = new EntityResource(
      $this->resourceTypeRepository->get($route->getRequirement('_entity_type'), $route->getRequirement('_bundle')),
      $this->entityTypeManager,
      $this->fieldManager,
      $this->currentContext,
      $this->fieldTypeManager,
      $this->linkManager,
      $this->resourceTypeRepository
    );
    return $resource;
  }

}
