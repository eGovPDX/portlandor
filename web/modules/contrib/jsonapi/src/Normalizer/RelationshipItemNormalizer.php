<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\jsonapi\Normalizer\Value\RelationshipItemNormalizerValue;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi\Controller\EntityResource;
use Drupal\serialization\EntityResolver\UuidReferenceInterface;

/**
 * Converts the Drupal entity reference item object to a JSON API structure.
 *
 * @todo Remove the dependency on \Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer
 */
class RelationshipItemNormalizer extends FieldItemNormalizer implements UuidReferenceInterface {

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
    $normalizer_value = new RelationshipItemNormalizerValue(
      $values,
      $relationship_item->getTargetResourceType()
    );

    $host_field_name = $relationship_item->getParent()->getPropertyName();
    if (!empty($context['include']) && in_array($host_field_name, $context['include'])) {
      $context = $this->buildSubContext($context, $target_entity, $host_field_name);
      $entity_and_access = EntityResource::getEntityAndAccess($target_entity);
      $included_normalizer_value = $this
        ->jsonapiDocumentToplevelNormalizer
        ->buildNormalizerValue($entity_and_access['entity'], $format, $context);
      $normalizer_value->setInclude($included_normalizer_value);
      $normalizer_value->addCacheableDependency($entity_and_access['access']);
      $normalizer_value->addCacheableDependency($included_normalizer_value);
      // Add the cacheable dependency of the included item directly to the
      // response cacheable metadata. This is similar to the flatten include
      // data structure, instead of a content graph.
      if (!empty($context['cacheable_metadata'])) {
        $context['cacheable_metadata']->addCacheableDependency($normalizer_value);
      }
    }
    return $normalizer_value;
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

  /**
   * {@inheritdoc}
   */
  public function getUuid($data) {
    if (isset($data['uuid'])) {
      return NULL;
    }
    $uuid = $data['uuid'];
    // The value may be a nested array like $uuid[0]['value'].
    if (is_array($uuid) && isset($uuid[0]['value'])) {
      $uuid = $uuid[0]['value'];
    }
    return $uuid;
  }

}
