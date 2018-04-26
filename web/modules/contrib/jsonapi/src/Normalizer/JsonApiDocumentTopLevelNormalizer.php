<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\jsonapi\Context\CurrentContext;
use Drupal\jsonapi\Context\FieldResolver;
use Drupal\jsonapi\Exception\EntityAccessDeniedHttpException;
use Drupal\jsonapi\Normalizer\Value\JsonApiDocumentTopLevelNormalizerValue;
use Drupal\jsonapi\Resource\EntityCollection;
use Drupal\jsonapi\LinkManager\LinkManager;
use Drupal\jsonapi\Resource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\ResourceType\ResourceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;

/**
 * Normalizes the top-level document according to the JSON API specification.
 *
 * @see \Drupal\jsonapi\Resource\JsonApiDocumentTopLevel
 *
 * @internal
 */
class JsonApiDocumentTopLevelNormalizer extends NormalizerBase implements DenormalizerInterface, NormalizerInterface {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = JsonApiDocumentTopLevel::class;

  /**
   * The link manager to get the links.
   *
   * @var \Drupal\jsonapi\LinkManager\LinkManager
   */
  protected $linkManager;

  /**
   * The current JSON API request context.
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
   * The JSON API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * The field resolver.
   *
   * @var \Drupal\jsonapi\Context\FieldResolver
   */
  protected $fieldResolver;

  /**
   * Constructs a JsonApiDocumentTopLevelNormalizer object.
   *
   * @param \Drupal\jsonapi\LinkManager\LinkManager $link_manager
   *   The link manager to get the links.
   * @param \Drupal\jsonapi\Context\CurrentContext $current_context
   *   The current context.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The JSON API resource type repository.
   * @param \Drupal\jsonapi\Context\FieldResolver $field_resolver
   *   The JSON API field resolver.
   */
  public function __construct(LinkManager $link_manager, CurrentContext $current_context, EntityTypeManagerInterface $entity_type_manager, ResourceTypeRepositoryInterface $resource_type_repository, FieldResolver $field_resolver) {
    $this->linkManager = $link_manager;
    $this->currentContext = $current_context;
    $this->entityTypeManager = $entity_type_manager;
    $this->resourceTypeRepository = $resource_type_repository;
    $this->fieldResolver = $field_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    // Validate a few common errors in document formatting.
    $this->validateRequestBody($data);

    $context += [
      'on_relationship' => $this->currentContext->isOnRelationship(),
    ];
    $normalized = [];

    if (!empty($data['data']['attributes'])) {
      $normalized = $data['data']['attributes'];
    }

    if (!empty($data['data']['id'])) {
      $resource_type = $this->resourceTypeRepository->getByTypeName($data['data']['type']);
      $uuid_key = $this->entityTypeManager->getDefinition($resource_type->getEntityTypeId())->getKey('uuid');
      $normalized[$uuid_key] = $data['data']['id'];
    }

    if (!empty($data['data']['relationships'])) {
      // Turn all single object relationship data fields into an array of
      // objects.
      $relationships = array_map(function ($relationship) {
        if (isset($relationship['data']['type']) && isset($relationship['data']['id'])) {
          return ['data' => [$relationship['data']]];
        }
        else {
          return $relationship;
        }
      }, $data['data']['relationships']);

      // Get an array of ids for every relationship.
      $relationships = array_map(function ($relationship) {
        if (empty($relationship['data'])) {
          return [];
        }
        if (empty($relationship['data'][0]['id'])) {
          throw new BadRequestHttpException("No ID specified for related resource");
        }
        $id_list = array_column($relationship['data'], 'id');
        if (empty($relationship['data'][0]['type'])) {
          throw new BadRequestHttpException("No type specified for related resource");
        }
        if (!$resource_type = $this->resourceTypeRepository->getByTypeName($relationship['data'][0]['type'])) {
          throw new BadRequestHttpException("Invalid type specified for related resource: '" . $relationship['data'][0]['type'] . "'");
        }

        $entity_type_id = $resource_type->getEntityTypeId();
        try {
          $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
        }
        catch (PluginNotFoundException $e) {
          throw new BadRequestHttpException("Invalid type specified for related resource: '" . $relationship['data'][0]['type'] . "'");
        }
        // In order to maintain the order ($delta) of the relationships, we need
        // to load the entities and create a mapping between id and uuid.
        $related_entities = array_values($entity_storage->loadByProperties(['uuid' => $id_list]));
        $map = [];
        foreach ($related_entities as $related_entity) {
          $map[$related_entity->uuid()] = $related_entity->id();
        }

        // $id_list has the correct order of uuids. We stitch this together with
        // $map which contains loaded entities, and then bring in the correct
        // meta values from the relationship, whose deltas match with $id_list.
        $canonical_ids = [];
        foreach ($id_list as $delta => $uuid) {
          if (empty($map[$uuid])) {
            continue;
          }
          $reference_item = [
            'target_id' => $map[$uuid],
          ];
          if (isset($relationship['data'][$delta]['meta'])) {
            $reference_item += $relationship['data'][$delta]['meta'];
          }
          $canonical_ids[] = $reference_item;
        }

        return array_filter($canonical_ids);
      }, $relationships);

      // Add the relationship ids.
      $normalized = array_merge($normalized, $relationships);
    }
    // Override deserialization target class with the one in the ResourceType.
    $class = $context['resource_type']->getDeserializationTargetClass();

    return $this
      ->serializer
      ->denormalize($normalized, $class, $format, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    if (empty($context['resource_type'])) {
      $context['resource_type'] = $this->currentContext->getResourceType();
    }
    $value_extractor = $this->buildNormalizerValue($object->getData(), $format, $context);
    if (!empty($context['cacheable_metadata'])) {
      $context['cacheable_metadata']->addCacheableDependency($value_extractor);
    }
    $normalized = $value_extractor->rasterizeValue();
    $included = array_filter($value_extractor->rasterizeIncludes());
    if (!empty($included)) {
      foreach ($included as $included_item) {
        if ($included_item['data'] === FALSE) {
          unset($included_item['data']);
          $normalized = NestedArray::mergeDeep($normalized, $included_item);
        }
        else {
          $normalized['included'][] = $included_item['data'];
        }
      }
    }

    return $normalized;
  }

  /**
   * Build the normalizer value.
   *
   * @return \Drupal\jsonapi\Normalizer\Value\JsonApiDocumentTopLevelNormalizerValue
   *   The normalizer value.
   */
  public function buildNormalizerValue($data, $format = NULL, array $context = []) {
    if (empty($context['expanded'])) {
      $context += $this->expandContext($context['request'], $context['resource_type']);
    }

    if ($data instanceof EntityReferenceFieldItemListInterface) {
      return $this->serializer->normalize($data, $format, $context);
    }
    $is_collection = $data instanceof EntityCollection;
    $include_count = $context['resource_type']->includeCount();
    // To improve the logical workflow deal with an array at all times.
    $entities = $is_collection ? $data->toArray() : [$data];
    $context['has_next_page'] = $is_collection ? $data->hasNextPage() : FALSE;

    if ($include_count) {
      $context['total_count'] = $is_collection ? $data->getTotalCount() : 1;
    }
    $serializer = $this->serializer;
    $normalizer_values = array_map(function ($entity) use ($format, $context, $serializer) {
      return $serializer->normalize($entity, $format, $context);
    }, $entities);

    $link_context = [
      'link_manager' => $this->linkManager,
      'has_next_page' => $context['has_next_page'],
    ];

    if ($include_count) {
      $link_context['total_count'] = $context['total_count'];
    }

    return new JsonApiDocumentTopLevelNormalizerValue($normalizer_values, $context, $link_context, $is_collection);
  }

  /**
   * Expand the context information based on the current request context.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to get the URL params from to expand the context.
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The resource type to translate to internal fields.
   *
   * @return array
   *   The expanded context.
   */
  protected function expandContext(Request $request, ResourceType $resource_type) {
    // Translate ALL the includes from the public field names to the internal.
    $includes = array_filter(explode(',', $request->query->get('include')));
    $public_includes = array_map(function ($include_str) use ($resource_type) {
      $resolved = $this->fieldResolver->resolveInternal(
        $resource_type->getEntityTypeId(),
        $resource_type->getBundle(),
        trim($include_str)
      );
      // We don't need the entity information for the includes. Clean it.
      return preg_replace('/\.entity\./', '.', $resolved);
    }, $includes);
    // Build the expanded context.
    $context = [
      'account' => NULL,
      'sparse_fieldset' => NULL,
      'resource_type' => NULL,
      'include' => $public_includes,
      'expanded' => TRUE,
    ];
    if ($request->query->get('fields')) {
      $context['sparse_fieldset'] = array_map(function ($item) {
        return explode(',', $item);
      }, $request->query->get('fields'));
    }

    return $context;
  }

  /**
   * Performs mimimal validation of the document.
   */
  protected static function validateRequestBody(array $document) {
    // Ensure that the relationships key was not placed in the top level.
    if (isset($document['relationships']) && !empty($document['relationships'])) {
      throw new BadRequestHttpException("Found \"relationships\" within the document's top level. The \"relationships\" key must be within resource object.");
    }
    // Ensure that the resource object contains the "type" key.
    if (!isset($document['data']['type'])) {
      throw new BadRequestHttpException("Resource object must include a \"type\".");
    }
    // Ensure that the client provided ID is a valid UUID.
    if (isset($document['data']['id']) && !Uuid::isValid($document['data']['id'])) {
      // This should be a 422 response, but the JSON API specification dictates
      // a 403 Forbidden response. We follow the specification.
      throw new EntityAccessDeniedHttpException(NULL, AccessResult::forbidden(), '/data/id', 'IDs should be properly generated and formatted UUIDs as described in RFC 4122.');
    }
  }

}
