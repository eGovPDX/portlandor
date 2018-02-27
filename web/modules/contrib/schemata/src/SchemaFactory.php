<?php

namespace Drupal\schemata;

use Psr\Log\LoggerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\TypedData\TypedDataManager;

/**
 * Create an object of type Drupal\schemata\Schema\SchemaInterface.
 *
 * Identifying a specific classed object to use is currently handled by a
 * mapping function in the create() method. Swapping out different
 * SchemaInterface implementations is not currently effective.
 */
class SchemaFactory {

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * EntityTypeBundleInfo.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * TypedDataManager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * Constructs a SchemaBuilder.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The EntityTypeManager to extract details of entity types.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entity_type_bundle_info
   *   The EntityTypeManager to extract details of entity types.
   * @param \Drupal\Core\TypedData\TypedDataManager $typed_data_manager
   *   The TypedDataManager to extract meaning of individual Entity properties.
   */
  public function __construct(LoggerInterface $logger, EntityTypeManager $entity_type_manager, EntityTypeBundleInfo $entity_type_bundle_info, TypedDataManager $typed_data_manager) {
    $this->logger = $logger;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->typedDataManager = $typed_data_manager;
  }

  /**
   * Assemble a schema object based on the requested entity type.
   *
   * @param string $entity_type
   *   URL input specifying an entity type to be processed.
   * @param string $bundle
   *   URL input specifying an entity bundle to be processed. May be NULL
   *   for support of entities that do not have bundles.
   *
   * @return \Drupal\schemata\Schema\Schema
   *   A Schema object which can be processed as a Rest Resource response.
   *   This will likely be converted into an interface or base class here.
   */
  public function create($entity_type, $bundle = NULL) {
    $entity_type_plugin = $this->entityTypeManager->getDefinition($entity_type, FALSE);
    if (empty($entity_type_plugin)) {
      $this->logger->warning('Invalid Entity Type "%entity_type" specified.', [
        '%entity_type' => $entity_type,
      ]);
      return NULL;
    }
    elseif (!($entity_type_plugin->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface'))) {
      $this->logger->warning('Only Content Entities are supported for now.');
      return NULL;
    }

    if ($entity_type_plugin->getBundleEntityType()) {
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
    }
    if (!empty($bundle) && !array_key_exists($bundle, $bundles)) {
      $this->logger->warning('Specified Entity Bundle "%bundle" does not exist.', [
        '%bundle' => $bundle,
      ]);
      return NULL;
    }

    $data_definition = empty($bundle) ?
      $this->typedDataManager->createDataDefinition("entity:" . $entity_type) :
      $this->typedDataManager->createDataDefinition("entity:" . $entity_type . ":" . $bundle);

    if ($entity_type == 'node' && !empty($bundle)) {
      $class = '\Drupal\schemata\Schema\NodeSchema';
    }
    else {
      $class = '\Drupal\schemata\Schema\Schema';
    }

    $schema = new $class(
      $data_definition,
      $bundle,
      $data_definition->getPropertyDefinitions()
    );

    $this->logger->notice('Schema generated for Entity Type (%entity_type) and Bundle (%bundle).', [
      '%entity_type' => $entity_type,
      '%bundle' => empty($bundle) ? 'N/A' : $bundle,
    ]);

    return $schema;
  }

}
