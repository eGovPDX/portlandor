<?php

namespace Drupal\jsonapi\Routing;

use Drupal\Core\Authentication\AuthenticationCollectorInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi\Resource\JsonApiDocumentTopLevel;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes.
 *
 * @internal
 */
class Routes implements ContainerInjectionInterface {

  /**
   * The front controller for the JSON API routes.
   *
   * All routes will use this callback to bootstrap the JSON API process.
   *
   * @var string
   */
  const FRONT_CONTROLLER = 'jsonapi.request_handler:handle';

  /**
   * The JSON API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * The authentication collector.
   *
   * @var \Drupal\Core\Authentication\AuthenticationCollectorInterface
   */
  protected $authCollector;

  /**
   * List of providers.
   *
   * @var string[]
   */
  protected $providerIds;

  /**
   * Instantiates a Routes object.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The JSON API resource type repository.
   * @param \Drupal\Core\Authentication\AuthenticationCollectorInterface $auth_collector
   *   The authentication provider collector.
   */
  public function __construct(ResourceTypeRepositoryInterface $resource_type_repository, AuthenticationCollectorInterface $auth_collector) {
    $this->resourceTypeRepository = $resource_type_repository;
    $this->authCollector = $auth_collector;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /* @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository */
    $resource_type_repository = $container->get('jsonapi.resource_type.repository');
    /* @var \Drupal\Core\Authentication\AuthenticationCollectorInterface $auth_collector */
    $auth_collector = $container->get('authentication_collector');

    return new static($resource_type_repository, $auth_collector);
  }

  /**
   * Provides the entry point route.
   */
  public function entryPoint() {
    $collection = new RouteCollection();

    $path_prefix = $this->resourceTypeRepository->getPathPrefix();
    $route_collection = (new Route('/' . $path_prefix, [
      RouteObjectInterface::CONTROLLER_NAME => '\Drupal\jsonapi\Controller\EntryPoint::index',
    ]))
      ->setRequirement('_permission', 'access jsonapi resource list')
      ->setMethods(['GET']);
    $route_collection->addOptions([
      '_auth' => $this->authProviderList(),
      '_is_jsonapi' => TRUE,
    ]);
    $collection->add('jsonapi.resource_list', $route_collection);

    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $collection = new RouteCollection();
    foreach ($this->resourceTypeRepository->all() as $resource_type) {
      if ($resource_type->isInternal()) {
        continue;
      }

      $path_prefix = $this->resourceTypeRepository->getPathPrefix();
      $resource_path = $resource_type->getPath();
      $route_base_path = sprintf('/%s/%s', $path_prefix, $resource_path);
      $build_route_name = function ($key) use ($resource_type) {
        return sprintf('jsonapi.%s.%s', $resource_type->getTypeName(), $key);
      };

      $defaults = [
        RouteObjectInterface::CONTROLLER_NAME => static::FRONT_CONTROLLER,
        'resource_type' => $resource_type->getTypeName(),
      ];
      // Options that apply to all routes.
      $options = [
        '_auth' => $this->authProviderList(),
        '_is_jsonapi' => TRUE,
      ];

      $parameters = [
        'resource_type' => [
          'type' => 'jsonapi_resource_type',
        ],
      ];

      // Collection endpoint, like /jsonapi/file/photo.
      $route_collection = (new Route($route_base_path))
        ->addDefaults($defaults + ['serialization_class' => JsonApiDocumentTopLevel::class])
        ->setRequirement('_entity_type', (string) $resource_type->getEntityTypeId())
        ->setRequirement('_bundle', (string) $resource_type->getBundle())
        ->setRequirement('_jsonapi_custom_query_parameter_names', 'TRUE')
        ->setRequirement('_csrf_request_header_token', 'TRUE')
        ->setOption('parameters', $parameters)
        ->setMethods(['GET', 'POST']);
      $route_collection->addOptions($options);
      $collection->add($build_route_name('collection'), $route_collection);

      // Individual endpoint, like /jsonapi/file/photo/123.
      $parameters = array_merge($parameters, [
        $resource_type->getEntityTypeId() => [
          'type' => 'entity:' . $resource_type->getEntityTypeId(),
        ],
      ]);
      $route_individual = (new Route(sprintf('%s/{%s}', $route_base_path, $resource_type->getEntityTypeId())))
        ->addDefaults($defaults + ['serialization_class' => JsonApiDocumentTopLevel::class])
        ->setRequirement('_entity_type', (string) $resource_type->getEntityTypeId())
        ->setRequirement('_bundle', (string) $resource_type->getBundle())
        ->setRequirement('_jsonapi_custom_query_parameter_names', 'TRUE')
        ->setRequirement('_csrf_request_header_token', 'TRUE')
        ->setOption('parameters', $parameters)
        ->setOption('_auth', $this->authProviderList())
        ->setMethods(['GET', 'PATCH', 'DELETE']);
      $route_individual->addOptions($options);
      $collection->add($build_route_name('individual'), $route_individual);

      // Related resource, like /jsonapi/file/photo/123/comments.
      $route_related = (new Route(sprintf('%s/{%s}/{related}', $route_base_path, $resource_type->getEntityTypeId())))
        ->addDefaults($defaults)
        ->setRequirement('_entity_type', (string) $resource_type->getEntityTypeId())
        ->setRequirement('_bundle', (string) $resource_type->getBundle())
        ->setRequirement('_jsonapi_custom_query_parameter_names', 'TRUE')
        ->setOption('parameters', $parameters)
        ->setOption('_auth', $this->authProviderList())
        ->setMethods(['GET']);
      $route_related->addOptions($options);
      $collection->add($build_route_name('related'), $route_related);

      // Related endpoint, like /jsonapi/file/photo/123/relationships/comments.
      $route_relationship = (new Route(sprintf('%s/{%s}/relationships/{related}', $route_base_path, $resource_type->getEntityTypeId())))
        ->addDefaults(
          $defaults + [
            '_on_relationship' => TRUE,
            'serialization_class' => EntityReferenceFieldItemList::class,
          ]
        )
        ->setRequirement('_entity_type', (string) $resource_type->getEntityTypeId())
        ->setRequirement('_bundle', (string) $resource_type->getBundle())
        ->setRequirement('_jsonapi_custom_query_parameter_names', 'TRUE')
        ->setRequirement('_csrf_request_header_token', 'TRUE')
        ->setOption('parameters', $parameters)
        ->setOption('_auth', $this->authProviderList())
        ->setMethods(['GET', 'POST', 'PATCH', 'DELETE']);
      $route_relationship->addOptions($options);
      $collection->add($build_route_name('relationship'), $route_relationship);
    }

    return $collection;
  }

  /**
   * Build a list of authentication provider ids.
   *
   * @return string[]
   *   The list of IDs.
   */
  protected function authProviderList() {
    if (isset($this->providerIds)) {
      return $this->providerIds;
    }
    $this->providerIds = array_keys($this->authCollector->getSortedProviders());

    return $this->providerIds;
  }

}
