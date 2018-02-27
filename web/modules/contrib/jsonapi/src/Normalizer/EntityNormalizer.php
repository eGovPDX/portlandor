<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\jsonapi\Normalizer\Value\EntityNormalizerValue;
use Drupal\jsonapi\Normalizer\Value\FieldNormalizerValueInterface;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\LinkManager\LinkManager;
use Drupal\jsonapi\Normalizer\Value\NullFieldNormalizerValue;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Converts the Drupal entity object to a JSON API array structure.
 */
class EntityNormalizer extends NormalizerBase implements DenormalizerInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = ContentEntityInterface::class;

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $formats = ['api_json'];

  /**
   * The link manager.
   *
   * @var \Drupal\jsonapi\LinkManager\LinkManager
   */
  protected $linkManager;

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
   * Constructs an EntityNormalizer object.
   *
   * @param \Drupal\jsonapi\LinkManager\LinkManager $link_manager
   *   The link manager.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The JSON API resource type repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(LinkManager $link_manager, ResourceTypeRepositoryInterface $resource_type_repository, EntityTypeManagerInterface $entity_type_manager) {
    $this->linkManager = $link_manager;
    $this->resourceTypeRepository = $resource_type_repository;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    // If the fields to use were specified, only output those field values.
    $context['resource_type'] = $resource_type = $this->resourceTypeRepository->get(
      $entity->getEntityTypeId(),
      $entity->bundle()
    );
    $resource_type_name = $resource_type->getTypeName();
    // Get the bundle ID of the requested resource. This is used to determine if
    // this is a bundle level resource or an entity level resource.
    $bundle = $resource_type->getBundle();
    if (!empty($context['sparse_fieldset'][$resource_type_name])) {
      $field_names = $context['sparse_fieldset'][$resource_type_name];
    }
    else {
      $field_names = $this->getFieldNames($entity, $bundle, $resource_type);
    }
    /* @var Value\FieldNormalizerValueInterface[] $normalizer_values */
    $normalizer_values = [];
    foreach ($this->getFields($entity, $bundle, $resource_type) as $field_name => $field) {
      if (!in_array($field_name, $field_names)) {
        continue;
      }
      $normalizer_values[$field_name] = $this->serializeField($field, $context, $format);
    }

    $link_context = ['link_manager' => $this->linkManager];
    $output = new EntityNormalizerValue($normalizer_values, $context, $entity, $link_context);
    // Add the entity level cacheability metadata.
    $output->addCacheableDependency($entity);
    $output->addCacheableDependency($output);
    // Add the field level cacheability metadata.
    array_walk($normalizer_values, function ($normalizer_value) {
      if ($normalizer_value instanceof RefinableCacheableDependencyInterface) {
        $normalizer_value->addCacheableDependency($normalizer_value);
      }
    });
    return $output;
  }

  /**
   * Checks if the passed field is a relationship field.
   *
   * @param mixed $field
   *   The field.
   *
   * @return bool
   *   TRUE if it's a JSON API relationship.
   */
  protected function isRelationship($field) {
    return $field instanceof EntityReferenceFieldItemList || $field instanceof Relationship;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    if (empty($context['resource_type']) || !$context['resource_type'] instanceof ResourceType) {
      throw new PreconditionFailedHttpException('Missing context during denormalization.');
    }
    /* @var \Drupal\jsonapi\ResourceType\ResourceType $resource_type */
    $resource_type = $context['resource_type'];
    $entity_type_id = $resource_type->getEntityTypeId();
    $bundle = $resource_type->getBundle();
    $bundle_key = $this->entityTypeManager->getDefinition($entity_type_id)
      ->getKey('bundle');
    if ($bundle_key && $bundle) {
      $data[$bundle_key] = $bundle;
    }

    return $this->entityTypeManager->getStorage($entity_type_id)
      ->create($this->prepareInput($data, $resource_type));
  }

  /**
   * Gets the field names for the given entity.
   *
   * @param mixed $entity
   *   The entity.
   * @param string $bundle
   *   The entity bundle.
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The resource type.
   *
   * @return string[]
   *   The field names.
   */
  protected function getFieldNames($entity, $bundle, ResourceType $resource_type) {
    /* @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    return array_keys($this->getFields($entity, $bundle, $resource_type));
  }

  /**
   * Gets the field names for the given entity.
   *
   * @param mixed $entity
   *   The entity.
   * @param string $bundle
   *   The bundle id.
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The resource type.
   *
   * @return array
   *   The fields.
   */
  protected function getFields($entity, $bundle, ResourceType $resource_type) {
    $output = [];
    $fields = $entity->getFields();
    // Filter the array based on the field names.
    $enabled_field_names = array_filter(
      array_keys($fields),
      [$resource_type, 'isFieldEnabled']
    );
    // Return a sub-array of $output containing the keys in $enabled_fields.
    $input = array_intersect_key($fields, array_flip($enabled_field_names));
    /* @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    foreach ($input as $field_name => $field_value) {
      $public_field_name = $resource_type->getPublicName($field_name);
      $output[$public_field_name] = $field_value;
    }
    return $output;
  }

  /**
   * Serializes a given field.
   *
   * @param mixed $field
   *   The field to serialize.
   * @param array $context
   *   The normalization context.
   * @param string $format
   *   The serialization format.
   *
   * @return Value\FieldNormalizerValueInterface
   *   The normalized value.
   */
  protected function serializeField($field, array $context, $format) {
    /* @var \Drupal\Core\Field\FieldItemListInterface|\Drupal\jsonapi\Normalizer\Relationship $field */
    // Continue if the current user does not have access to view this field.
    $access = $field->access('view', $context['account'], TRUE);
    if ($field instanceof AccessibleInterface && !$access->isAllowed()) {
      return (new NullFieldNormalizerValue())->addCacheableDependency($access);
    }
    /** @var \Drupal\jsonapi\Normalizer\Value\FieldNormalizerValue $output */
    $output = $this->serializer->normalize($field, $format, $context);
    if (!$output instanceof FieldNormalizerValueInterface) {
      return new NullFieldNormalizerValue();
    }
    $is_relationship = $this->isRelationship($field);
    $property_type = $is_relationship ? 'relationships' : 'attributes';
    $output->setPropertyType($property_type);

    if ($output instanceof RefinableCacheableDependencyInterface) {
      // Add the cache dependency to the field level object because we want to
      // allow the field normalizers to add extra cacheability metadata.
      $output->addCacheableDependency($access);
    }

    return $output;
  }

  /**
   * Prepares the input data to create the entity.
   *
   * @param array $data
   *   The input data to modify.
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   Contains the info about the resource type.
   *
   * @return array
   *   The modified input data.
   */
  protected function prepareInput(array $data, ResourceType $resource_type) {
    $data_internal = [];
    // Translate the public fields into the entity fields.
    foreach ($data as $public_field_name => $field_value) {
      // Skip any disabled field.
      if (!$resource_type->isFieldEnabled($public_field_name)) {
        continue;
      }
      $internal_name = $resource_type->getInternalName($public_field_name);
      $data_internal[$internal_name] = $field_value;
    }

    return $data_internal;
  }

}
