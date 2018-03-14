<?php

namespace Drupal\jsonapi\Context;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Service which resolves public field names to and from Drupal field names.
 *
 * @internal
 */
class FieldResolver {

  /**
   * The entity type id.
   *
   * @var \Drupal\jsonapi\Context\CurrentContext
   */
  protected $currentContext;

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
   * The entity type bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The JSON API resource type repository service.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * Creates a FieldResolver instance.
   *
   * @param \Drupal\jsonapi\Context\CurrentContext $current_context
   *   The JSON API CurrentContext service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The bundle info service.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The resource type repository.
   */
  public function __construct(CurrentContext $current_context, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, ResourceTypeRepositoryInterface $resource_type_repository) {
    $this->currentContext = $current_context;
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldManager = $field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->resourceTypeRepository = $resource_type_repository;
  }

  /**
   * Maps a Drupal field name to a public field name.
   *
   * Example:
   *   'field_author.entity.field_first_name' -> 'author.firstName'.
   *
   * @param string $internal_field_name
   *   The Drupal field name to map to a public field name.
   *
   * @return string
   *   The mapped field name.
   */
  public function resolveExternal($internal_field_name) {
    $resource_type = $this->currentContext->getResourceType();
    return $resource_type->getPublicName($internal_field_name);
  }

  /**
   * Resolves external field expressions into internal field expressions.
   *
   * It is often required to reference data which may exist across a
   * relationship. For example, you may want to sort a list of articles by
   * a field on the article author's representative entity. Or you may wish
   * to filter a list of content by the name of referenced taxonomy terms.
   *
   * In an effort to simplify the referenced paths and align them with the
   * structure of JSON API responses and the structure of the hypothetical
   * "reference document" (see link), it is possible to alias field names and
   * elide the "entity" keyword from them (this word is used by the entity query
   * system to traverse entity references).
   *
   * This method takes this external field expression and and attempts to
   * resolve any aliases and/or abbreviations into a field expression that will
   * be compatible with the entity query system.
   *
   * @link http://jsonapi.org/recommendations/#urls-reference-document
   *
   * Example:
   *   'uid.field_first_name' -> 'uid.entity.field_first_name'.
   *   'author.firstName' -> 'field_author.entity.field_first_name'
   *
   * @param string $entity_type_id
   *   The type of the entity for which to resolve the field name.
   * @param string $bundle
   *   The bundle of the entity for which to resolve the field name.
   * @param string $external_field_name
   *   The public field name to map to a Drupal field name.
   *
   * @return string
   *   The mapped field name.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   */
  public function resolveInternal($entity_type_id, $bundle, $external_field_name) {
    $resource_type = $this->resourceTypeRepository->get($entity_type_id, $bundle);
    if (empty($external_field_name)) {
      throw new BadRequestHttpException('No field name was provided for the filter.');
    }

    // Turns 'uid.categories.name' into
    // 'uid.entity.field_category.entity.name'. This may be too simple, but it
    // works for the time being.
    $parts = explode('.', $external_field_name);
    $reference_breadcrumbs = [];
    /* @var \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types */
    $resource_types = [$resource_type];
    while ($part = array_shift($parts)) {
      $field_name = $this->getInternalName($part, $resource_types);

      // If none of the resource types are traversable, assume that the
      // remaining path parts are for sub-properties.
      if (!$this->resourceTypesAreTraversable($resource_types)) {
        $reference_breadcrumbs[] = $field_name;
        return $this->constructInternalPath($reference_breadcrumbs, $parts);
      }

      $candidate_definitions = $this->getFieldItemDefinitions(
        $resource_types,
        $field_name
      );

      // If there are no definitions, then the field does not exist.
      if (empty($candidate_definitions)) {
        throw new BadRequestHttpException(sprintf(
          'Invalid nested filtering. The field `%s`, given in the path `%s`, does not exist.',
          $part,
          $external_field_name
        ));
      }

      // Get all of the referenceable resource types.
      $resource_types = $this->getReferenceableResourceTypes($candidate_definitions);

      // Keep a trail of entity reference field names.
      $reference_breadcrumbs[] = $field_name;

      // $field_name may not be a reference field. In that case we should treat
      // the rest of the parts as sub-properties of the field.
      if (empty($resource_types)) {
        return $this->constructInternalPath($reference_breadcrumbs, $parts);
      }
    }

    // Reconstruct the full path to the final reference field.
    return $this->constructInternalPath($reference_breadcrumbs);
  }

  /**
   * Expands the internal path with the "entity" keyword.
   *
   * @param string[] $references
   *   The resolved internal field names of all entity references.
   * @param string[] $property_path
   *   (optional) A sub-property path for the last field in the path.
   *
   * @return string
   *   The expanded and imploded path.
   */
  protected function constructInternalPath(array $references, array $property_path = []) {
    // Reconstruct the path parts that are referencing sub-properties.
    $field_path = implode('.', $property_path);

    // This rebuilds the path from the real, internal field names that have
    // been traversed so far. It joins them with the "entity" keyword as
    // required by the entity query system.
    $entity_path = implode('.entity.', $references);

    // Reconstruct the full path to the final reference field.
    return (empty($field_path)) ? $entity_path : $entity_path . '.' . $field_path;
  }

  /**
   * Get all item definitions from a set of resources types by a field name.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
   *   The resource types on which the field might exist.
   * @param string $field_name
   *   The field for which to retrieve field item definitions.
   *
   * @return \Drupal\Core\TypedData\ComplexDataDefinitionInterface[]
   *   The found field item definitions.
   */
  protected function getFieldItemDefinitions(array $resource_types, $field_name) {
    return array_reduce($resource_types, function ($result, $resource_type) use ($field_name) {
      /* @var \Drupal\jsonapi\ResourceType\ResourceType $resource_type */
      $entity_type = $resource_type->getEntityTypeId();
      $bundle = $resource_type->getBundle();
      $definitions = $this->fieldManager->getFieldDefinitions($entity_type, $bundle);
      if (isset($definitions[$field_name])) {
        $result[] = $definitions[$field_name]->getItemDefinition();
      }
      return $result;
    }, []);
  }

  /**
   * Resolves the internal field name based on a collection of resource types.
   *
   * @param string $field_name
   *   The external field name.
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
   *   The resource types from which to get an internal name.
   *
   * @return string
   *   The resolved internal name.
   */
  protected function getInternalName($field_name, array $resource_types) {
    return array_reduce($resource_types, function ($carry, ResourceType $resource_type) use ($field_name) {
      if ($carry != $field_name) {
        // We already found the internal name.
        return $carry;
      }
      return $resource_type->getInternalName($field_name);
    }, $field_name);
  }

  /**
   * Get the referenceable ResourceTypes for a set of field definitions.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $definitions
   *   The resource types on which the reference field might exist.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType[]
   *   The referenceable target resource types.
   */
  protected function getReferenceableResourceTypes(array $definitions) {
    return array_reduce($definitions, function ($result, $definition) {
      $resource_types = array_filter(
        $this->collectResourceTypesForReference($definition)
      );
      $type_names = array_map(function ($resource_type) {
        /* @var \Drupal\jsonapi\ResourceType\ResourceType $resource_type */
        return $resource_type->getTypeName();
      }, $resource_types);
      return array_merge($result, array_combine($type_names, $resource_types));
    }, []);
  }

  /**
   * Build a list of resource types depending on which bundles are referenced.
   *
   * @param \Drupal\Core\Field\TypedData\FieldItemDataDefinition $item_definition
   *   The reference definition.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType[]
   *   The list of resource types.
   *
   * @todo Add PHP type hint, see
   *   https://www.drupal.org/project/jsonapi/issues/2933895
   */
  protected function collectResourceTypesForReference(FieldItemDataDefinition $item_definition) {
    $main_property_definition = $item_definition->getPropertyDefinition(
      $item_definition->getMainPropertyName()
    );

    // Check if the field is a flavor of an Entity Reference field.
    if (!$main_property_definition instanceof DataReferenceTargetDefinition) {
      return [];
    }
    $entity_type_id = $item_definition->getSetting('target_type');
    $handler_settings = $item_definition->getSetting('handler_settings');

    $has_target_bundles = isset($handler_settings['target_bundles']) && !empty($handler_settings['target_bundles']);
    $target_bundles = $has_target_bundles ?
      $handler_settings['target_bundles']
      : $this->getAllBundlesForEntityType($entity_type_id);

    return array_map(function ($bundle) use ($entity_type_id) {
      return $this->resourceTypeRepository->get($entity_type_id, $bundle);
    }, $target_bundles);
  }

  /**
   * Whether the given resources can be traversed to other resources.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
   *   The resources types to evaluate.
   *
   * @return bool
   *   TRUE if any one of the given resource types is traversable.
   *
   * @todo This class shouldn't be aware of entity types and their definitions.
   * Whether a resource can have relationships to other resources is information
   * we ought to be able to discover on the ResourceType. However, we cannot
   * reliably determine this information with existing APIs. Entities may be
   * backed by various storages that are unable to perform queries across
   * references and certain storages may not be able to store references at all.
   */
  protected function resourceTypesAreTraversable(array $resource_types) {
    foreach ($resource_types as $resource_type) {
      $entity_type_definition = $this->entityTypeManager->getDefinition($resource_type->getEntityTypeId());
      if ($entity_type_definition->entityClassImplements(FieldableEntityInterface::class)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Gets all bundle IDs for a given entity type.
   *
   * @param string $entity_type_id
   *   The entity type for which to get bundles.
   *
   * @return string[]
   *   The bundle IDs.
   */
  protected function getAllBundlesForEntityType($entity_type_id) {
    return array_keys($this->entityTypeBundleInfo->getBundleInfo($entity_type_id));
  }

}
