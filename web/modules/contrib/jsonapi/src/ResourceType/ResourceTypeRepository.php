<?php

namespace Drupal\jsonapi\ResourceType;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

/**
 * Provides a repository of all JSON API resource types.
 *
 * Contains the complete set of ResourceType value objects, which are auto-
 * generated based on the Entity Type Manager and Entity Type Bundle Info: one
 * JSON API resource type per entity type bundle. So, for example:
 * - node--article
 * - node--page
 * - node--…
 * - user--user
 * - …
 *
 * @see \Drupal\jsonapi\ResourceType\ResourceType
 *
 * @internal
 */
class ResourceTypeRepository implements ResourceTypeRepositoryInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The bundle manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleManager;

  /**
   * All JSON API resource types.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceType[]
   */
  protected $all = [];

  /**
   * Instantiates a ResourceTypeRepository object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_manager
   *   The bundle manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleManager = $bundle_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function all() {
    if (!$this->all) {
      $entity_type_ids = array_keys($this->entityTypeManager->getDefinitions());
      foreach ($entity_type_ids as $entity_type_id) {
        $this->all = array_merge($this->all, array_map(function ($bundle) use ($entity_type_id) {
          return new ResourceType(
            $entity_type_id,
            $bundle,
            $this->entityTypeManager->getDefinition($entity_type_id)->getClass()
          );
        }, array_keys($this->bundleManager->getBundleInfo($entity_type_id))));
      }
    }
    return $this->all;
  }

  /**
   * {@inheritdoc}
   */
  public function get($entity_type_id, $bundle) {
    if (empty($entity_type_id)) {
      throw new PreconditionFailedHttpException('Server error. The current route is malformed.');
    }
    foreach ($this->all() as $resource) {
      if ($resource->getEntityTypeId() == $entity_type_id && $resource->getBundle() == $bundle) {
        return $resource;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getByTypeName($type_name) {
    foreach ($this->all() as $resource) {
      if ($resource->getTypeName() == $type_name) {
        return $resource;
      }
    }
    return NULL;
  }

}
