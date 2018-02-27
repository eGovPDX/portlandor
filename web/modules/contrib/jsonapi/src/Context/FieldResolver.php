<?php

namespace Drupal\jsonapi\Context;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
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
   * The field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * Creates a FieldResolver instance.
   *
   * @param \Drupal\jsonapi\Context\CurrentContext $current_context
   *   The JSON API CurrentContext service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The bundle info service.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The resource type repository.
   */
  public function __construct(CurrentContext $current_context, EntityFieldManagerInterface $field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, ResourceTypeRepositoryInterface $resource_type_repository) {
    $this->currentContext = $current_context;
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
   * Maps a public field name to a Drupal field name.
   *
   * Example:
   *   'author.firstName' -> 'field_author.entity.field_first_name'.
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
   */
  public function resolveInternal($entity_type_id, $bundle, $external_field_name) {
    $resource_type = $this->resourceTypeRepository->get($entity_type_id, $bundle);
    if (empty($external_field_name)) {
      throw new BadRequestHttpException('No field name was provided for the filter.');
    }
    // Right now we are exposing all the fields with the name they have in
    // the Drupal backend. But this may change in the future.
    if (strpos($external_field_name, '.') === FALSE) {
      return $resource_type->getInternalName($external_field_name);
    }
    // Turns 'uid.categories.name' into
    // 'uid.entity.field_category.entity.name'. This may be too simple, but it
    // works for the time being.
    $parts = explode('.', $external_field_name);
    $reference_breadcrumbs = [];
    $resource_types = [$resource_type];
    while ($field_name = array_shift($parts)) {
      $field_name = $this->getInternalName($field_name, $resource_types);
      $definitions = $this->fieldManager->getFieldStorageDefinitions($entity_type_id)
        // We also need the base field definitions in case there are
        // relationships coming from computed fields.
        + $this->fieldManager->getBaseFieldDefinitions($entity_type_id);
      if (!$definitions) {
        throw new BadRequestHttpException(sprintf(
          'Invalid nested filtering. There is no entity type "%s".',
          $entity_type_id
        ));
      }
      if (empty($definitions[$field_name])) {
        throw new BadRequestHttpException(sprintf(
          'Invalid nested filtering. Invalid entity reference "%s".',
          $field_name
        ));
      }
      array_push($reference_breadcrumbs, $field_name);
      // Update the resource type with the referenced type.
      $resource_types = $this->collectResourceTypesForReference($definitions[$field_name]);
      // Update the entity type with the referenced type.
      $entity_type_id = $definitions[$field_name]->getSetting('target_type');
      // $field_name may not be a reference field. In that case we should treat
      // the rest of the parts as complex fields.
      if (empty($entity_type_id)) {
        // This is the path from the initial entity type to the entity type that
        // contains $field_name. This path is a set of entity references.
        $entity_path = implode('.entity.', $reference_breadcrumbs);
        // This is the path from the final entity type to the selected field
        // column.
        $field_path = implode('.', $parts);

        return implode('.', array_filter([$entity_path, $field_path]));
      }
    }

    return implode('.entity.', $reference_breadcrumbs);
  }

  /**
   * Resolves the internal field name based on a collection of resource types.
   *
   * @param string $field_name
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
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
   * Build a list of resource types depending on which bundles are referenced.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The reference definition.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType[]
   *   The list of resource types.
   */
  protected function collectResourceTypesForReference(FieldStorageDefinitionInterface $field_definition) {
    // Check if the field is a flavor of an Entity Reference field.
    $main_property_definition = $field_definition->getPropertyDefinition(
      $field_definition->getMainPropertyName()
    );
    if (!$main_property_definition instanceof DataReferenceTargetDefinition) {
      return [];
    }
    $entity_type_id = $field_definition->getSetting('target_type');
    $handler_settings = $field_definition->getSetting('handler_settings');
    if (empty($handler_settings['target_bundles'])) {
      // If target bundles is NULL it means ALL of the bundles in the entity ID
      // are referenceable.
      $bundle_info = $this->entityTypeBundleInfo
        ->getBundleInfo($entity_type_id);
      $target_bundles = array_keys($bundle_info);
    }
    return array_map(function ($bundle) use ($entity_type_id) {
      return $this->resourceTypeRepository->get($entity_type_id, $bundle);
    }, $target_bundles);
  }

}
