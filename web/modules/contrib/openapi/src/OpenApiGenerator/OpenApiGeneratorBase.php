<?php

namespace Drupal\openapi\OpenApiGenerator;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\schemata\SchemaFactory;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Base class for OpenAPI generators.
 */
abstract class OpenApiGeneratorBase implements OpenApiGeneratorInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routingProvider;

  /**
   * The Field Manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * The Schemata SchemaFactory.
   *
   * @var \Drupal\schemata\SchemaFactory
   */
  protected $schemaFactory;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * OpenApiGeneratorBase constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $routingProvider
   *   The routing provider.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $fieldManager
   *   The field manager.
   * @param \Drupal\schemata\SchemaFactory $schemaFactory
   *   The schema factory.
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The serializer.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RouteProviderInterface $routingProvider, EntityFieldManagerInterface $fieldManager, SchemaFactory $schemaFactory, SerializerInterface $serializer) {
    $this->entityTypeManager = $entityTypeManager;
    $this->fieldManager = $fieldManager;
    $this->routingProvider = $routingProvider;
    $this->schemaFactory = $schemaFactory;
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public function getSpecification(array $options = []) {
    $spec = [
      'swagger' => "2.0",
      'schemes' => ['http'],
      'info' => $this->getInfo(),
      'host' => \Drupal::request()->getHost(),
      'basePath' => $this->getBasePath(),
      'securityDefinitions' => $this->getSecurityDefinitions(),
      'tags' => $this->getTags($options),
      'definitions' => $this->getDefinitions($options),
      'paths' => $this->getPaths($options),
    ];
    return $spec;
  }

  /**
   * Creates the 'info' portion of the API.
   *
   * @return array
   *   The info elements.
   */
  protected function getInfo() {
    $site_name = \Drupal::config('system.site')->get('name');
    return [
      'description' => $this->getApiDescription(),
      'title' => $site_name . ' - ' . $this->getApiName(),
      'version' => 'Versioning not supported',
    ];
  }

  /**
   * Gets the API name.
   *
   * @return string
   *   The API name.
   */
  abstract public function getApiName();

  /**
   * {@inheritdoc}
   */
  public function getBasePath() {
    return \Drupal::request()->getBasePath();
  }

  /**
   * {@inheritdoc}
   */
  public function getSecurityDefinitions() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTags(array $options = []) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions(array $options = []) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPaths(array $options = []) {
    return [];
  }

  /**
   * Gets the JSON Schema for an entity type or entity type and bundle.
   *
   * @param string $described_format
   *   The format that will be described, json, json_api, etc.
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle_name
   *   The bundle name.
   *
   * @return array
   *   The JSON schema.
   */
  protected function getJsonSchema($described_format, $entity_type_id, $bundle_name = NULL) {
    if ($entity_type_id !== $bundle_name) {
      $schema = $this->schemaFactory->create($entity_type_id, $bundle_name);
    }
    else {
      $schema = $this->schemaFactory->create($entity_type_id);
    }

    if ($schema) {
      $json_schema = $this->serializer->normalize($schema, "schema_json:$described_format");
      unset($json_schema['$schema'], $json_schema['id']);
      $json_schema = $this->cleanSchema($json_schema);
      if (!$bundle_name) {
        // Add discriminator field.
        $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
        if ($bundle_field = $entity_type->getKey('bundle')) {
          $json_schema['discriminator'] = $entity_type->getKey('bundle');
        }
      }
    }
    else {
      $json_schema = [
        'type' => 'object',
        'title' => $this->t('@entity_type Schema', ['@entity_type' => $entity_type_id]),
        'description' => $this->t('Describes the payload for @entity_type entities.', ['@entity_type' => $entity_type_id]),
      ];
    }
    return $json_schema;
  }

  /**
   * Cleans JSON schema definitions for OpenAPI.
   *
   * @todo Just to test if fixes
   *       https://github.com/OAI/OpenAPI-Specification/issues/229
   *
   * @param array $json_schema
   *   The JSON Schema elements.
   *
   * @return array
   *   The cleaned JSON Schema elements.
   */
  protected function cleanSchema(array $json_schema) {
    foreach ($json_schema as $key => &$value) {
      if ($value === NULL) {
        $value = '';
      }
      else {
        if (is_array($value)) {
          $this->fixDefaultFalse($value);
          $value = $this->cleanSchema($value);
        }
      }
    }
    return $json_schema;
  }

  /**
   * Fix default field value as zero instead of FALSE.
   *
   * @param array $value
   *   JSON Schema field value.
   */
  protected function fixDefaultFalse(array &$value) {
    if (isset($value['type']) && $value['type'] == 'array'
      && is_array($value['items']['properties'])
    ) {
      foreach ($value['items']['properties'] as $property_key => $property) {
        if ($property['type'] == 'boolean') {
          if (isset($value['default'][0][$property_key]) && empty($value['default'][0][$property_key])) {
            $value['default'][0][$property_key] = FALSE;
          }
        }
      }
    }
  }

  /**
   * Get possible responses for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $method
   *   The method.
   * @param string $bundle_name
   *   The bundle name.
   *
   * @return array
   *   The entity responses.
   */
  protected function getEntityResponses($entity_type_id, $method, $bundle_name = NULL) {
    $method = strtolower($method);
    $responses = [];

    $schema_response = [];
    if ($definition_ref = $this->getDefinitionReference($entity_type_id, $bundle_name)) {
      $schema_response = [
        'schema' => [
          '$ref' => $definition_ref,
        ],
      ];
    }

    switch ($method) {
      case 'get':
        $responses['200'] = [
          'description' => 'successful operation',
          ] + $schema_response;
        break;

      case 'post':
        unset($responses['200']);
        $responses['201'] = [
          'description' => 'Entity created',
          ] + $schema_response;
        break;

      case 'delete':
        unset($responses['200']);
        $responses['201'] = [
          'description' => 'Entity deleted',
        ];
        break;
    }
    return $responses;
  }

  /**
   * Gets the reference to the definition in the document.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle_name
   *   The bundle name.
   *
   * @return string
   *   The reference to the definition.
   */
  protected function getDefinitionReference($entity_type_id, $bundle_name) {
    $definition_key = $this->getEntityDefinitionKey($entity_type_id, $bundle_name);
    if ($this->definitionExists($definition_key)) {
      $definition_ref = '#/definitions/' . $definition_key;
      return $definition_ref;
    }
    return '';
  }

  /**
   * Gets the entity definition key.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $bundle_name
   *   The bundle name.
   *
   * @return string
   *   The entity definition key. Either [entity_type] or
   *   [entity_type]:[bundle_name]
   */
  protected function getEntityDefinitionKey($entity_type_id, $bundle_name) {
    $definition_key = $entity_type_id;
    if ($bundle_name) {
      $definition_key .= ":$bundle_name";
      return $definition_key;
    }
    return $definition_key;
  }

  /**
   * Check whether a definitions exists for a key.
   *
   * @param string $definition_key
   *   The definition to check.
   *
   * @return bool
   *   TRUE if it exists.
   */
  protected function definitionExists($definition_key) {
    $definitions = $this->getDefinitions();
    return isset($definitions[$definition_key]);
  }

  /**
   * Determines if an entity type and/or bundle show be included.
   *
   * @param array $options
   *   The options to generate the output.
   * @param string $entity_type_id
   *   The entity type id.
   * @param string|null $bundle_name
   *   The bundle name.
   *
   * @return bool
   *   True if the entity type or bundle should be included.
   */
  protected function includeEntityTypeBundle(array $options, $entity_type_id, $bundle_name = NULL) {
    // Entity types or a specific bundle be can excluded.
    if (isset($options['exclude'])) {
      if (array_intersect([$entity_type_id, "$entity_type_id:$bundle_name"], $options['exclude'])) {
        return FALSE;
      }
    }
    if (isset($options['entity_mode'])) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      if ($options['entity_mode'] == 'content_entities') {
        return $entity_type instanceof ContentEntityTypeInterface;
      }
      if ($options['entity_mode'] == 'config_entities') {
        return $entity_type instanceof ConfigEntityTypeInterface;
      }
    }
    if (isset($options['entity_type_id']) && $options['entity_type_id'] !== $entity_type_id) {
      return FALSE;
    }
    if (isset($options['bundle_name']) && $options['bundle_name'] !== $bundle_name) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Gets API description.
   *
   * @return string
   *   The API Description.
   */
  abstract protected function getApiDescription();

}
