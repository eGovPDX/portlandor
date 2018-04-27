<?php

namespace Drupal\openapi\OpenApiGenerator;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\rest\Plugin\Type\ResourcePluginManager;
use Drupal\rest\RestResourceConfigInterface;
use Drupal\schemata\SchemaFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Route;
use Symfony\Component\Serializer\Serializer;

/**
 * Generates for OpenAPI specification for REST.
 */
class OpenApiRestGenerator extends OpenApiGeneratorBase {

  use RestInspectionTrait;
  /**
   * The plugin manager for REST plugins.
   *
   * @var \Drupal\rest\Plugin\Type\ResourcePluginManager
   */
  protected $manager;

  /**
   * Constructs a new OpenApiController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $routing_provider
   *   The route provider.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The field manager.
   * @param \Drupal\schemata\SchemaFactory $schema_factory
   *   The schema factory.
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer.
   * @param \Drupal\rest\Plugin\Type\ResourcePluginManager $rest_manager
   *   The resource plugin manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RouteProviderInterface $routing_provider, EntityFieldManagerInterface $field_manager, SchemaFactory $schema_factory, Serializer $serializer, ResourcePluginManager $rest_manager) {
    parent::__construct($entity_type_manager, $routing_provider, $field_manager, $schema_factory, $serializer);
    $this->manager = $rest_manager;
  }

  /**
   * Return resources for non-entity resources.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A json response.
   */
  public function nonBundleResourcesJson() {
    /** @var \Drupal\rest\Entity\RestResourceConfig[] $resource_configs */
    $resource_configs = $this->entityTypeManager
      ->getStorage('rest_resource_config')
      ->loadMultiple();
    $non_entity_configs = [];
    foreach ($resource_configs as $resource_config) {
      if (!$this->isEntityResource($resource_config)) {
        $non_entity_configs[] = $resource_config;
      }
      else {
        $entity_type = $this->getEntityType($resource_config);
        if (!$entity_type->getBundleEntityType()) {
          $non_entity_configs[] = $resource_config;
        }
      }
    }
    $spec = $this->getSpecification($non_entity_configs);
    $response = new JsonResponse($spec);
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions(array $options = []) {
    $bundle_name = isset($options['bundle_name']) ? $options['bundle_name'] : NULL;
    $entity_type_id = isset($options['entity_type_id']) ? $options['entity_type_id'] : NULL;
    static $definitions = [];
    if (!$definitions) {
      $entity_types = $this->getRestEnabledEntityTypes($entity_type_id);
      $definitions = [];
      foreach ($entity_types as $entity_id => $entity_type) {
        $entity_schema = $this->getJsonSchema('json', $entity_id);
        $definitions[$entity_id] = $entity_schema;
        if ($bundle_type = $entity_type->getBundleEntityType()) {
          $bundle_storage = $this->entityTypeManager->getStorage($bundle_type);
          if ($bundle_name) {
            $bundles[$bundle_name] = $bundle_storage->load($bundle_name);
          }
          else {
            $bundles = $bundle_storage->loadMultiple();
          }
          foreach ($bundles as $bundle_name => $bundle) {
            $bundle_schema = $this->getJsonSchema('json', $entity_id, $bundle_name);
            foreach ($entity_schema['properties'] as $property_id => $property) {
              if (isset($bundle_schema['properties'][$property_id]) && $bundle_schema['properties'][$property_id] === $property) {
                // Remove any bundle schema property that is the same as the
                // entity schema property.
                unset($bundle_schema['properties'][$property_id]);
              }
            }
            // Use Open API polymorphism support to show that bundles extend
            // entity type.
            // @todo Should base fields be removed from bundle schema?
            // @todo Can base fields could be different from entity type base fields?
            // @see hook_entity_bundle_field_info().
            // @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#models-with-polymorphism-support
            $definitions[$this->getEntityDefinitionKey($entity_type->id(), $bundle_name)] = [
              'allOf' => [
                ['$ref' => "#/definitions/$entity_id"],
                $bundle_schema,
              ],
            ];

          }
        }
      }
    }

    return $definitions;
  }

  /**
   * Gets available security definitions.
   *
   * @return array
   *   The security definitions.
   */
  public function getSecurityDefinitions() {
    // @todo Determine definitions from available auth.
    return [
      'csrf_token' => [
        'type' => 'apiKey',
        'name' => 'X-CSRF-Token',
        'in' => 'header',
      ],
      'basic_auth' => [
        'type' => 'basic',
      ],
    ];
  }

  /**
   * Get tags.
   */
  public function getTags(array $options = []) {
    $entity_types = $this->getRestEnabledEntityTypes();
    $tags = [];
    foreach ($entity_types as $entity_type) {
      $tag = [
        'name' => $entity_type->id(),
        'description' => $this->t("Entity type: @label", ['@label' => $entity_type->getLabel()]),
        'x-entity-type' => $entity_type->id(),
      ];
      $tags[] = $tag;
    }
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaths(array $options = []) {
    $bundle_name = isset($options['bundle_name']) ? $options['bundle_name'] : NULL;
    $resource_configs = $this->getResourceConfigs($options);
    if (!$resource_configs) {
      return [];
    }
    $api_paths = [];
    foreach ($resource_configs as $id => $resource_config) {
      /** @var \Drupal\rest\Plugin\ResourceBase $plugin */
      $resource_plugin = $resource_config->getResourcePlugin();
      foreach ($resource_config->getMethods() as $method) {
        if ($route = $this->getRouteForResourceMethod($resource_config, $method)) {
          $open_api_method = strtolower($method);
          $path = $route->getPath();
          $path_method_spec = [];
          $formats = $resource_config->getFormats($method);
          $format_parameter = [
            'name' => '_format',
            'in' => 'query',
            'type' => 'string',
            'enum' => $formats,
            'required' => TRUE,
            'description' => 'Request format',
          ];
          if (count($formats) == 1) {
            $format_parameter['default'] = $formats[0];
          }
          $path_method_spec['parameters'][] = $format_parameter;

          $path_method_spec['responses'] = $this->getErrorResponses();

          if ($this->isEntityResource($resource_config)) {
            $entity_type = $this->getEntityType($resource_config);
            $path_method_spec['tags'] = [$entity_type->id()];
            $path_method_spec['summary'] = $this->t('@method a @entity_type', [
              '@method' => ucfirst($open_api_method),
              '@entity_type' => $entity_type->getLabel(),
            ]);
            /*foreach ($formats as $format) {
              $path_method_spec['consumes'][] = "$format";
              $path_method_spec['produces'][] = "$format";
            }*/

            $path_method_spec['parameters'] = array_merge($path_method_spec['parameters'], $this->getEntityParameters($entity_type, $method, $bundle_name));
            $path_method_spec['responses'] = $this->getEntityResponses($entity_type->id(), $method, $bundle_name) + $path_method_spec['responses'];
          }
          else {
            $path_method_spec['responses']['200'] = [
              'description' => 'successful operation',
            ];
            $path_method_spec['summary'] = $resource_plugin->getPluginDefinition()['label'];
            $path_method_spec['parameters'] = array_merge($path_method_spec['parameters'], $this->getRouteParameters($route));

          }

          $path_method_spec['operationId'] = $resource_plugin->getPluginId() . ":" . $method;
          $path_method_spec['schemes'] = ['http'];
          $path_method_spec['security'] = $this->getSecurity($resource_config, $method, $formats);
          $api_paths[$path][$open_api_method] = $path_method_spec;
        }
      }
    }
    return $api_paths;
  }

  /**
   * Gets the matching for route for the resource and method.
   *
   * @param \Drupal\rest\RestResourceConfigInterface $resource_config
   *   The REST config resource.
   * @param string $method
   *   The HTTP method.
   *
   * @return \Symfony\Component\Routing\Route
   *   The route.
   *
   * @throws \Exception
   *   If no route is found.
   */
  protected function getRouteForResourceMethod(RestResourceConfigInterface $resource_config, $method) {
    if ($this->isEntityResource($resource_config)) {
      $route_name = 'rest.' . $resource_config->id() . ".$method";

      $routes = $this->routingProvider->getRoutesByNames([$route_name]);
      if (empty($routes)) {
        $formats = $resource_config->getFormats($method);
        if (count($formats) > 0) {
          $route_name .= ".{$formats[0]}";
          $routes = $this->routingProvider->getRoutesByNames([$route_name]);
        }
      }
      if ($routes) {
        return array_pop($routes);
      }
    }
    else {
      $resource_plugin = $resource_config->getResourcePlugin();
      foreach ($resource_plugin->routes() as $route) {
        $methods = $route->getMethods();
        if (array_search($method, $methods) !== FALSE) {
          return $route;
        }
      };
    }
    throw new \Exception("No route found for REST resource, {$resource_config->id()}, for method $method");
  }

  /**
   * Get the error responses.
   *
   * @see https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md#responseObject
   *
   * @return array
   *   Keys are http codes. Values responses.
   */
  protected function getErrorResponses() {
    $responses['400'] = [
      'description' => 'Bad request',
      'schema' => [
        'type' => 'object',
        'properties' => [
          'error' => [
            'type' => 'string',
            'example' => 'Bad data',
          ],
        ],
      ],
    ];
    $responses['500'] = [
      'description' => 'Internal server error.',
      'schema' => [
        'type' => 'object',
        'properties' => [
          'message' => [
            'type' => 'string',
            'example' => 'Internal server error.',
          ],
        ],
      ],
    ];
    return $responses;
  }

  /**
   * Get parameters for an entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param string $method
   *   The HTTP method.
   * @param string $bundle_name
   *   The bundle name.
   *
   * @return array
   *   Parameters for the entity resource.
   */
  protected function getEntityParameters(EntityTypeInterface $entity_type, $method, $bundle_name = NULL) {
    $parameters = [];
    if (in_array($method, ['GET', 'DELETE', 'PATCH'])) {
      $keys = $entity_type->getKeys();
      if ($entity_type instanceof ConfigEntityTypeInterface) {
        $key_type = 'string';
      }
      else {
        if ($entity_type instanceof FieldableEntityInterface) {
          $key_field = $this->fieldManager->getFieldStorageDefinitions($entity_type->id())[$keys['id']];
          $key_type = $key_field->getType();
        }
        else {
          $key_type = 'string';
        }

      }

      $parameters[] = [
        'name' => $entity_type->id(),
        'in' => 'path',
        'required' => TRUE,
        'type' => $key_type,
        'description' => $this->t('The @id,id, of the @type.', [
          '@id' => $keys['id'],
          '@type' => $entity_type->id(),
        ]),
      ];
    }
    if (in_array($method, ['POST', 'PATCH'])) {
      $parameters[] = [
        'name' => 'body',
        'in' => 'body',
        'description' => $this->t('The @label object', ['@label' => $entity_type->getLabel()]),
        'required' => TRUE,
        'schema' => [
          '$ref' => '#/definitions/' . $this->getEntityDefinitionKey($entity_type->id(), $bundle_name),
        ],
      ];
    }
    return $parameters;
  }

  /**
   * Get OpenAPI parameters for a route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route.
   *
   * @return array
   *   The resource parameters.
   */
  protected function getRouteParameters(Route $route) {
    $parameters = [];
    $vars = $route->compile()->getPathVariables();
    foreach ($vars as $var) {
      $parameters[] = [
        'name' => $var,
        'type' => 'string',
        'in' => 'path',
        'required' => TRUE,
      ];
    }
    return $parameters;
  }

  /**
   * Get the security information for the a resource.
   *
   * @param \Drupal\rest\RestResourceConfigInterface $resource_config
   *   The REST resource.
   * @param string $method
   *   The HTTP method.
   * @param string[] $formats
   *   The formats.
   *
   * @return array
   *   The security elements.
   *
   * @see http://swagger.io/specification/#securityDefinitionsObject
   */
  protected function getSecurity(RestResourceConfigInterface $resource_config, $method, array $formats) {
    $security = [];
    foreach ($resource_config->getAuthenticationProviders($method) as $auth) {
      switch ($auth) {
        case 'basic_auth':
          $security[] = ['basic_auth' => []];
      }
    }
    // @todo Handle tokens that need to be set in headers.

    if ($this->isEntityResource($resource_config)) {

      $route_name = 'rest.' . $resource_config->id() . ".$method";

      $routes = $this->routingProvider->getRoutesByNames([$route_name]);
      if (empty($routes) && count($formats) > 1) {
        $route_name .= ".{$formats[0]}";
        $routes = $this->routingProvider->getRoutesByNames([$route_name]);
      }
      if ($routes) {
        $route = array_pop($routes);
        // Check to see if route is protected by access checks in header.
        if ($route->getRequirement('_csrf_request_header_token')) {
          $security[] = ['csrf_token' => []];
        }
      }
    }
    return $security;
  }

  /**
   * {@inheritdoc}
   */
  public function getApiName() {
    return $this->t('REST API');
  }

  /**
   * {@inheritdoc}
   */
  protected function getApiDescription() {
    return $this->t('The REST API provide by the core REST module.');
  }

}
