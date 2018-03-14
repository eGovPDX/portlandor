<?php

namespace Drupal\jsonapi\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\jsonapi\Context\CurrentContext;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
class RequestHandler implements ContainerAwareInterface, ContainerInjectionInterface {

  use ContainerAwareTrait;

  protected static $requiredCacheContexts = ['user.permissions'];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
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
    /* @var \Symfony\Component\Serializer\SerializerInterface $serializer */
    $serializer = $this->container->get('jsonapi.serializer_do_not_use_removal_imminent');
    /* @var \Drupal\jsonapi\Context\CurrentContext $current_context */
    $current_context = $this->container->get('jsonapi.current_context');
    $current_context->reset();
    $unserialized = $this->deserializeBody($request, $serializer, $route->getOption('serialization_class'), $current_context);
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
    $resource = $this->resourceFactory($route, $current_context);

    // Only add the unserialized data if there is something there.
    $extra_parameters = $unserialized ? [$unserialized, $request] : [$request];

    // Execute the request in context so the cacheable metadata from the entity
    // grants system is caught and added to the response. This is surfaced when
    // executing the underlying entity query.
    $context = new RenderContext();
    /** @var \Drupal\Core\Cache\CacheableResponseInterface $response */
    $response = $this->container->get('renderer')
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
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The serializer for the deserialization of the input data.
   * @param string $serialization_class
   *   The class the input data needs to deserialize into.
   * @param \Drupal\jsonapi\Context\CurrentContext $current_context
   *   The current context.
   *
   * @return mixed
   *   The deserialized data or a Response object in case of error.
   */
  public function deserializeBody(Request $request, SerializerInterface $serializer, $serialization_class, CurrentContext $current_context) {
    $received = $request->getContent();
    if (empty($received) || $request->isMethodCacheable()) {
      return NULL;
    }
    $resource_type = $current_context->getResourceType();
    $field_related = $resource_type->getInternalName($request->get('related'));
    try {
      return $serializer->deserialize($received, $serialization_class, 'api_json', [
        'related' => $field_related,
        'target_entity' => $request->get($current_context->getResourceType()->getEntityTypeId()),
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
   * @param \Drupal\jsonapi\Context\CurrentContext $current_context
   *   The current context.
   *
   * @return \Drupal\jsonapi\Controller\EntityResource
   *   The instantiated resource.
   */
  protected function resourceFactory(Route $route, CurrentContext $current_context) {
    /** @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository */
    $resource_type_repository = $this->container->get('jsonapi.resource_type.repository');
    /* @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /* @var \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager */
    $field_manager = $this->container->get('entity_field.manager');
    /* @var \Drupal\Core\Field\FieldTypePluginManagerInterface $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.field.field_type');
    /** @var \Drupal\jsonapi\LinkManager\LinkManager $link_manager */
    $link_manager = $this->container->get('jsonapi.link_manager');
    $resource = new EntityResource(
      $resource_type_repository->get($route->getRequirement('_entity_type'), $route->getRequirement('_bundle')),
      $entity_type_manager,
      $field_manager,
      $current_context,
      $plugin_manager,
      $link_manager,
      $resource_type_repository
    );
    return $resource;
  }

}
