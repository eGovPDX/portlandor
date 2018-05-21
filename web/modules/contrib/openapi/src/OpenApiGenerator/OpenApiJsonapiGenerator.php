<?php

namespace Drupal\openapi\OpenApiGenerator;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Symfony\Component\Routing\Route;

/**
 * Generates for OpenAPI specification for JSON API.
 */
class OpenApiJsonapiGenerator extends OpenApiGeneratorBase {

  const JSON_API_UUID_CONVERTER = 'paramconverter.jsonapi.entity_uuid';

  /**
   * {@inheritdoc}
   */
  public function getBasePath() {
    return parent::getBasePath() . '/jsonapi';
  }

  /**
   * {@inheritdoc}
   */
  public function getPaths(array $options = []) {
    $routes = $this->getJsonApiRoutes();
    $api_paths = [];
    foreach ($routes as $route_name => $route) {
      $entity_type_id = $route->getRequirement('_entity_type');
      $bundle_name = $route->getRequirement('_bundle');
      if (!$this->includeEntityTypeBundle($options, $entity_type_id, $bundle_name)) {
        continue;
      }
      $api_path = [];
      $methods = $route->getMethods();
      foreach ($methods as $method) {
        $method = strtolower($method);
        $path_method = [];
        $path_method['summary'] = $this->getRouteMethodSummary($route, $route_name, $method);
        $path_method['description'] = '@todo Add descriptions';
        $path_method['parameters'] = $this->getMethodParameters($route, $method);
        $path_method['tags'] = [$this->getBundleTag($entity_type_id, $bundle_name)];
        $path_method['responses'] = $this->getEntityResponses($entity_type_id, $method, $bundle_name, $route_name);
        $api_path[$method] = $path_method;
      }
      // Each path contains the "base path" from a OpenAPI perspective.
      $path = str_replace('/jsonapi', '', $route->getPath());
      $api_paths[$path] = $api_path;
    }
    return $api_paths;
  }

  /**
   * Gets the JSON API routes.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   The routes.
   */
  protected function getJsonApiRoutes() {
    $all_routes = $this->routingProvider->getAllRoutes();
    $jsonapi_reroutes = [];
    foreach ($all_routes as $route_name => $route) {
      if ($route_name === 'jsonapi.resource_list') {
        continue;
      }
      if ($route->getOption('_is_jsonapi')) {
        $jsonapi_reroutes[$route_name] = $route;
      }
    }
    return $jsonapi_reroutes;
  }

  /**
   * Gets description of a method on a route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route.
   * @param string $route_name
   *   The route name.
   * @param string $method
   *   The method.
   *
   * @return string
   *   The method summary.
   */
  protected function getRouteMethodSummary(Route $route, $route_name, $method) {
    // @todo Make a better summary.
    if ($route_type = $this->getRoutTypeFromName($route_name)) {
      return "$route_type $method";
    }
    return '@todo';

  }

  /**
   * Gets the route from the name if possible.
   *
   * @param string $route_name
   *   The route name.
   *
   * @return string
   *   The route type.
   */
  protected function getRoutTypeFromName($route_name) {
    $route_name_parts = explode('.', $route_name);
    return isset($route_name_parts[2]) ? $route_name_parts[2] : '';
  }

  /**
   * Get the parameters array for a method on a route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route.
   * @param string $method
   *   The HTTP method.
   *
   * @return array
   *   The parameters.
   */
  protected function getMethodParameters(Route $route, $method) {
    $parameters = [];
    $entity_type_id = $route->getRequirement('_entity_type');
    $bundle_name = $route->getRequirement('_bundle');
    if ($route->hasOption('parameters')) {
      foreach ($route->getOption('parameters') as $parameter_name => $parameter_info) {
        $parameter = [
          'name' => $parameter_name,
          'required' => TRUE,
          'in' => 'path',
        ];
        if ($parameter_info['converter'] == static::JSON_API_UUID_CONVERTER) {
          $parameter['type'] = 'uuid';
          $parameter['description'] = $this->t('The uuid of the @entity @bundle',
            [
              '@entity' => $entity_type_id,
              '@bundle' => $bundle_name,
            ]
          );
        }
        $parameters[] = $parameter;
      }
    }
    else {
      if ($method == 'get') {
        // If no route parameters and GET then this is collection route.
        // @todo Add descriptions or link to documentation.
        $parameters[] = [
          'name' => 'filter',
          'in' => 'query',
          'type' => 'array',
          'required' => FALSE,
          // 'description' => '@todo Explain filtering: https://www.drupal.org/docs/8/modules/json-api/collections-filtering-sorting-and-paginating',
        ];
        $parameters[] = [
          'name' => 'sort',
          'in' => 'query',
          'type' => 'array',
          'required' => FALSE,
          // 'description' => '@todo Explain sorting: https://www.drupal.org/docs/8/modules/json-api/collections-filtering-sorting-and-paginating',
        ];
        $parameters[] = [
          'name' => 'page',
          'in' => 'query',
          'type' => 'array',
          'required' => FALSE,
          // 'description' => '@todo Explain sorting: https://www.drupal.org/docs/8/modules/json-api/collections-filtering-sorting-and-paginating',
        ];
      }
      elseif ($method == 'post' || $method == 'patch') {
        // Determine if it is ContentEntity.
        if ($this->entityTypeManager->getDefinition($entity_type_id) instanceof ContentEntityTypeInterface) {
          $parameters[] = [
            'name' => 'body',
            'in' => 'body',
            'description' => $this->t('The @label object', ['@label' => $entity_type_id]),
            'required' => TRUE,
            'schema' => [
              '$ref' => '#/definitions/' . "$entity_type_id:$bundle_name",
            ],
          ];
        }

      }
    }
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityResponses($entity_type_id, $method, $bundle_name = NULL, $route_name = NULL) {
    $route_type = $this->getRoutTypeFromName($route_name);
    if ($route_type === 'collection') {
      if ($method === 'get') {
        $schema_response = [];
        if ($definition_ref = $this->getDefinitionReference($entity_type_id, $bundle_name)) {
          $schema_response = [
            'schema' => [
              'type' => 'array',
              'items' => [
                '$ref' => $definition_ref,
              ],
            ],
          ];
        }
        $responses['200'] = [
            'description' => 'successful operation',
          ] + $schema_response;
        return $responses;
      }

    }
    else {
      return parent::getEntityResponses($entity_type_id, $method, $bundle_name);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions(array $options = []) {
    static $definitions = [];
    if (!$definitions) {
      foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
        if ($entity_type instanceof ContentEntityTypeInterface) {
          if ($bundle_type = $entity_type->getBundleEntityType()) {
            $bundle_storage = $this->entityTypeManager->getStorage($bundle_type);
            $bundles = $bundle_storage->loadMultiple();
            foreach ($bundles as $bundle_name => $bundle) {
              if ($this->includeEntityTypeBundle($options, $entity_type->id(), $bundle_name)) {
                $definitions["{$entity_type->id()}:$bundle_name"] = $this->getJsonSchema('api_json', $entity_type->id(), $bundle_name);
              }
            }
          }
          else {
            if ($this->includeEntityTypeBundle($options, $entity_type->id())) {
              $definitions["{$entity_type->id()}:{$entity_type->id()}"] = $this->getJsonSchema('api_json', $entity_type->id());
            }
          }
        }
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  protected function getJsonSchema($described_format, $entity_type_id, $bundle_name = NULL) {
    $json_schema = parent::getJsonSchema($described_format, $entity_type_id, $bundle_name);
    // @todo Should the schemata module be adding these?
    $json_schema['properties'] += [
      'type' => [
        'type' => 'string',
        'title' => $this->t('Title'),
        'example' => "$entity_type_id--$bundle_name",
      ],
      'id' => [
        'type' => 'string',
        'title' => $this->t('Id'),
      ],
    ];
    return $json_schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getTags(array $options = []) {
    $tags = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      if ($bundle_type_id = $entity_type->getBundleEntityType()) {
        $bundle_storage = $this->entityTypeManager->getStorage($bundle_type_id);
        $bundles = $bundle_storage->loadMultiple();
        foreach ($bundles as $bundle_name => $bundle) {
          if (!$this->includeEntityTypeBundle($options, $entity_type->id(), $bundle_name)) {
            continue;
          }
          $description = $this->t("@bundle_label @bundle of type @entity_type.",
            [
              '@bundle_label' => $entity_type->getBundleLabel(),
              '@bundle' => $bundle->label(),
              '@entity_type' => $entity_type->getLabel(),
            ]
          );
          $tag = [
            'name' => $this->getBundleTag($entity_type->id(), $bundle->id()),
            'description' => $description,
          ];
          if (method_exists($bundle, 'getDescription')) {
            $tag['description'] .= ' ' . $bundle->getDescription();
          }
          $tags[] = $tag;
        }
      }
      else {
        if (!$this->includeEntityTypeBundle($options, $entity_type->id())) {
          continue;
        }
        $tag = [
          'name' => $this->getBundleTag($entity_type->id()),
        ];
        if ($entity_type instanceof ConfigEntityTypeInterface) {
          $tag['description'] = $this->t('Configuration entity @entity_type', ['@entity_type' => $entity_type->getLabel()]);
        }
        $tags[] = $tag;
      }
    }
    return $tags;
  }

  /**
   * Get the tag to use for a bundle.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $bundle_name
   *   The entity type.
   *
   * @return string
   *   The bundle tag.
   */
  protected function getBundleTag($entity_type_id, $bundle_name = NULL) {
    static $tags = [];
    if (!isset($tags[$entity_type_id][$bundle_name])) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $tag = $entity_type->getLabel();
      if ($bundle_type_id = $entity_type->getBundleEntityType()) {
        $bundle_entity = $this->entityTypeManager->getStorage($bundle_type_id)->load($bundle_name);
        $tag .= ' - ' . $bundle_entity->label();
      }
      $tags[$entity_type_id][$bundle_name] = $tag;
    }
    return $tags[$entity_type_id][$bundle_name];
  }

  /**
   * {@inheritdoc}
   */
  public function getApiName() {
    return $this->t('JSON API');
  }

  /**
   * {@inheritdoc}
   */
  protected function getApiDescription() {
    return $this->t('This is a JSON API compliant implemenation');
  }

}
