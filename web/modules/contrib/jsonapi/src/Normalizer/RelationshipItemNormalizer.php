<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\jsonapi\Normalizer\Value\RelationshipItemNormalizerValue;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi\Controller\EntityResource;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Converts the Drupal entity reference item object to a JSON API structure.
 *
 * @todo Remove the dependency on \Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer
 *
 * @internal
 */
class RelationshipItemNormalizer extends FieldItemNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = RelationshipItem::class;

  /**
   * The JSON API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * The JSON API document top level normalizer.
   *
   * @var \Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer
   */
  protected $jsonapiDocumentToplevelNormalizer;

  /**
   * Instantiates a RelationshipItemNormalizer object.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The JSON API resource type repository.
   * @param \Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer $jsonapi_document_toplevel_normalizer
   *   The document root normalizer for the include.
   */
  public function __construct(ResourceTypeRepositoryInterface $resource_type_repository, JsonApiDocumentTopLevelNormalizer $jsonapi_document_toplevel_normalizer) {
    $this->resourceTypeRepository = $resource_type_repository;
    $this->jsonapiDocumentToplevelNormalizer = $jsonapi_document_toplevel_normalizer;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Remove this override when the dependency on \Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer is removed.
   */
  public function setSerializer(SerializerInterface $serializer) {
    parent::setSerializer($serializer);
    $this->jsonapiDocumentToplevelNormalizer->setSerializer($serializer);
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($relationship_item, $format = NULL, array $context = []) {
    /* @var $relationship_item \Drupal\jsonapi\Normalizer\RelationshipItem */
    // TODO: We are always loading the referenced entity. Even if it is not
    // going to be included. That may be a performance issue. We do it because
    // we need to know the entity type and bundle to load the JSON API resource
    // type for the relationship item. We need a better way of finding about
    // this.
    $target_entity = $relationship_item->getTargetEntity();
    $values = $relationship_item->getValue();
    if (isset($context['langcode'])) {
      $values['lang'] = $context['langcode'];
    }

    $host_field_name = $relationship_item->getParent()->getPropertyName();
    if (!empty($context['include']) && in_array($host_field_name, $context['include'])) {
      $context = $this->buildSubContext($context, $target_entity, $host_field_name);
      $entity_and_access = EntityResource::getEntityAndAccess($target_entity);
      $included_normalizer_value = $this
        ->jsonapiDocumentToplevelNormalizer
        ->buildNormalizerValue($entity_and_access['entity'], $format, $context);
    }
    else {
      $included_normalizer_value = NULL;
    }

    return new RelationshipItemNormalizerValue(
      $values,
      new CacheableMetadata(),
      $relationship_item->getTargetResourceType(),
      $included_normalizer_value
    );
  }

  /**
   * Builds the sub-context for the relationship include.
   *
   * @param array $context
   *   The serialization context.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The related entity.
   * @param string $host_field_name
   *   The name of the field reference.
   *
   * @return array
   *   The modified new context.
   */
  protected function buildSubContext(array $context, EntityInterface $entity, $host_field_name) {
    // Swap out the context for the context of the referenced resource.
    $context['resource_type'] = $this->resourceTypeRepository
      ->get($entity->getEntityTypeId(), $entity->bundle());
    // Since we're going one level down the only includes we need are the ones
    // that apply to this level as well.
    $include_candidates = array_filter($context['include'], function ($include) use ($host_field_name) {
      return strpos($include, $host_field_name . '.') === 0;
    });
    $context['include'] = array_map(function ($include) use ($host_field_name) {
      return str_replace($host_field_name . '.', '', $include);
    }, $include_candidates);
    return $context;
  }

}
