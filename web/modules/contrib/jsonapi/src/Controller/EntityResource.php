<?php

namespace Drupal\jsonapi\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\jsonapi\Context\CurrentContext;
use Drupal\jsonapi\Exception\EntityAccessDeniedHttpException;
use Drupal\jsonapi\Exception\UnprocessableHttpEntityException;
use Drupal\jsonapi\Query\Filter;
use Drupal\jsonapi\Query\Sort;
use Drupal\jsonapi\Query\OffsetPage;
use Drupal\jsonapi\LinkManager\LinkManager;
use Drupal\jsonapi\Resource\EntityCollection;
use Drupal\jsonapi\Resource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi\ResourceType\ResourceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @see \Drupal\jsonapi\Controller\RequestHandler
 * @internal
 */
class EntityResource {

  /**
   * The JSON API resource type.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceType
   */
  protected $resourceType;

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
   * The current context service.
   *
   * @var \Drupal\jsonapi\Context\CurrentContext
   */
  protected $currentContext;

  /**
   * The current context service.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The link manager service.
   *
   * @var \Drupal\jsonapi\LinkManager\LinkManager
   */
  protected $linkManager;

  /**
   * Instantiates a EntityResource object.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON API resource type.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The entity type field manager.
   * @param \Drupal\jsonapi\Context\CurrentContext $current_context
   *   The current context.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $plugin_manager
   *   The plugin manager for fields.
   * @param \Drupal\jsonapi\LinkManager\LinkManager $link_manager
   *   The link manager service.
   */
  public function __construct(ResourceType $resource_type, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $field_manager, CurrentContext $current_context, FieldTypePluginManagerInterface $plugin_manager, LinkManager $link_manager) {
    $this->resourceType = $resource_type;
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldManager = $field_manager;
    $this->currentContext = $current_context;
    $this->pluginManager = $plugin_manager;
    $this->linkManager = $link_manager;
  }

  /**
   * Gets the individual entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The loaded entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param int $response_code
   *   The response code. Defaults to 200.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   */
  public function getIndividual(EntityInterface $entity, Request $request, $response_code = 200) {
    $entity_access = $entity->access('view', NULL, TRUE);
    if (!$entity_access->isAllowed()) {
      throw new EntityAccessDeniedHttpException($entity, $entity_access, '/data', 'The current user is not allowed to GET the selected resource.');
    }
    $response = $this->buildWrappedResponse($entity, $response_code);
    return $response;
  }

  /**
   * Verifies that the whole entity does not violate any validation constraints.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @throws \Drupal\jsonapi\Exception\EntityAccessDeniedHttpException
   *   If validation errors are found.
   */
  protected function validate(EntityInterface $entity) {
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }

    $violations = $entity->validate();

    // Remove violations of inaccessible fields as they cannot stem from our
    // changes.
    $violations->filterByFieldAccess();

    if (count($violations) > 0) {
      // Instead of returning a generic 400 response we use the more specific
      // 422 Unprocessable Entity code from RFC 4918. That way clients can
      // distinguish between general syntax errors in bad serializations (code
      // 400) and semantic errors in well-formed requests (code 422).
      $exception = new UnprocessableHttpEntityException();
      $exception->setViolations($violations);
      throw $exception;
    }
  }

  /**
   * Creates an individual entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The loaded entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   */
  public function createIndividual(EntityInterface $entity, Request $request) {
    $entity_access = $entity->access('create', NULL, TRUE);

    if (!$entity_access->isAllowed()) {
      throw new EntityAccessDeniedHttpException($entity, $entity_access, '/data', 'The current user is not allowed to POST the selected resource.');
    }
    $this->validate($entity);

    // Return a 409 Conflict response in accordance with the JSON API spec. See
    // http://jsonapi.org/format/#crud-creating-responses-409.
    if ($this->entityExists($entity)) {
      throw new ConflictHttpException('Conflict: Entity already exists.');
    }

    $entity->save();

    // Build response object.
    $response = $this->getIndividual($entity, $request, 201);

    // According to JSON API specification, when a new entity was created
    // we should send "Location" header to the frontend.
    $entity_url = $this->linkManager->getEntityLink(
      $entity->uuid(),
      $this->resourceType,
      [],
      'individual'
    );
    $response->headers->set('Location', $entity_url);

    // Return response object with updated headers info.
    return $response;
  }

  /**
   * Patches an individual entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The loaded entity.
   * @param \Drupal\Core\Entity\EntityInterface $parsed_entity
   *   The entity with the new data.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   */
  public function patchIndividual(EntityInterface $entity, EntityInterface $parsed_entity, Request $request) {
    $entity_access = $entity->access('update', NULL, TRUE);
    if (!$entity_access->isAllowed()) {
      throw new EntityAccessDeniedHttpException($entity, $entity_access, '/data', 'The current user is not allowed to PATCH the selected resource.');
    }
    $body = Json::decode($request->getContent());
    $data = $body['data'];
    if ($data['id'] != $entity->uuid()) {
      throw new BadRequestHttpException(sprintf(
        'The selected entity (%s) does not match the ID in the payload (%s).',
        $entity->uuid(),
        $data['id']
      ));
    }
    $data += ['attributes' => [], 'relationships' => []];
    $field_names = array_merge(array_keys($data['attributes']), array_keys($data['relationships']));
    array_reduce($field_names, function (EntityInterface $destination, $field_name) use ($parsed_entity) {
      $this->updateEntityField($parsed_entity, $destination, $field_name);
      return $destination;
    }, $entity);

    $this->validate($entity);
    $entity->save();
    return $this->getIndividual($entity, $request);
  }

  /**
   * Deletes an individual entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The loaded entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   */
  public function deleteIndividual(EntityInterface $entity, Request $request) {
    $entity_access = $entity->access('delete', NULL, TRUE);
    if (!$entity_access->isAllowed()) {
      throw new EntityAccessDeniedHttpException($entity, $entity_access, '/data', 'The current user is not allowed to DELETE the selected resource.');
    }
    $entity->delete();
    return new ResourceResponse(NULL, 204);
  }

  /**
   * Gets the collection of entities.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   */
  public function getCollection(Request $request) {
    // Instantiate the query for the filtering.
    $entity_type_id = $this->resourceType->getEntityTypeId();

    $route_params = $request->attributes->get('_route_params');
    $params = isset($route_params['_json_api_params']) ? $route_params['_json_api_params'] : [];
    $query = $this->getCollectionQuery($entity_type_id, $params);

    $results = $query->execute();

    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    // We request N+1 items to find out if there is a next page for the pager. We may need to remove that extra item
    // before loading the entities.
    $pager_size = $query->getMetaData('pager_size');
    if ($has_next_page = $pager_size < count($results)) {
      // Drop the last result.
      array_pop($results);
    }
    // Each item of the collection data contains an array with 'entity' and
    // 'access' elements.
    $collection_data = $this->loadEntitiesWithAccess($storage, $results);
    $entity_collection = new EntityCollection(array_column($collection_data, 'entity'));
    $entity_collection->setHasNextPage($has_next_page);

    // Calculate all the results and pass them to the EntityCollectionInterface.
    if ($this->resourceType->includeCount()) {
      $total_results = $this
        ->getCollectionCountQuery($entity_type_id, $params)
        ->count()
        ->execute();

      $entity_collection->setTotalCount($total_results);
    }

    $response = $this->respondWithCollection($entity_collection, $entity_type_id);

    // Add cacheable metadata for the access result.
    $access_info = array_column($collection_data, 'access');
    array_walk($access_info, function ($access) use ($response) {
      $response->addCacheableDependency($access);
    });

    return $response;
  }

  /**
   * Gets the related resource.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The requested entity.
   * @param string $related_field
   *   The related field name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   */
  public function getRelated(EntityInterface $entity, $related_field, Request $request) {
    $related_field = $this->resourceType->getInternalName($related_field);
    if (!($field_list = $entity->get($related_field)) || !$this->isRelationshipField($field_list)) {
      throw new NotFoundHttpException(sprintf('The relationship %s is not present in this resource.', $related_field));
    }
    // Add the cacheable metadata from the host entity.
    $cacheable_metadata = CacheableMetadata::createFromObject($entity);
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemList $field_list */
    $is_multiple = $field_list
      ->getDataDefinition()
      ->getFieldStorageDefinition()
      ->isMultiple();
    if (!$is_multiple) {
      $response = $this->getIndividual($field_list->entity, $request);
      // Add cacheable metadata for host entity to individual response.
      $response->addCacheableDependency($cacheable_metadata);
      return $response;
    }
    $collection_data = [];
    foreach ($field_list->referencedEntities() as $referenced_entity) {
      /* @var \Drupal\Core\Entity\EntityInterface $referenced_entity */
      $collection_data[$referenced_entity->id()] = static::getEntityAndAccess($referenced_entity);
      $cacheable_metadata->addCacheableDependency($referenced_entity);
    }
    $entity_collection = new EntityCollection(array_column($collection_data, 'entity'));
    $response = $this->buildWrappedResponse($entity_collection);

    $access_info = array_column($collection_data, 'access');
    array_walk($access_info, function ($access) use ($response) {
      $response->addCacheableDependency($access);
    });
    // $response does not contain the entity list cache tag. We add the
    // cacheable metadata for the finite list of entities in the relationship.
    $response->addCacheableDependency($cacheable_metadata);

    return $response;
  }

  /**
   * Gets the relationship of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The requested entity.
   * @param string $related_field
   *   The related field name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param int $response_code
   *   The response code. Defaults to 200.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   */
  public function getRelationship(EntityInterface $entity, $related_field, Request $request, $response_code = 200) {
    $related_field = $this->resourceType->getInternalName($related_field);
    if (!($field_list = $entity->get($related_field)) || !$this->isRelationshipField($field_list)) {
      throw new NotFoundHttpException(sprintf('The relationship %s is not present in this resource.', $related_field));
    }
    $response = $this->buildWrappedResponse($field_list, $response_code);
    return $response;
  }

  /**
   * Adds a relationship to a to-many relationship.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The requested entity.
   * @param string $related_field
   *   The related field name.
   * @param mixed $parsed_field_list
   *   The entity reference field list of items to add, or a response object in
   *   case of error.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   */
  public function createRelationship(EntityInterface $entity, $related_field, $parsed_field_list, Request $request) {
    $related_field = $this->resourceType->getInternalName($related_field);
    if ($parsed_field_list instanceof Response) {
      // This usually means that there was an error, so there is no point on
      // processing further.
      return $parsed_field_list;
    }
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $parsed_field_list */
    $this->relationshipAccess($entity, $related_field);
    // According to the specification, you are only allowed to POST to a
    // relationship if it is a to-many relationship.
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field_list */
    $field_list = $entity->{$related_field};
    $is_multiple = $field_list->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->isMultiple();
    if (!$is_multiple) {
      throw new ConflictHttpException(sprintf('You can only POST to to-many relationships. %s is a to-one relationship.', $related_field));
    }

    $field_access = $field_list->access('edit', NULL, TRUE);
    if (!$field_access->isAllowed()) {
      $field_name = $field_list->getName();
      throw new EntityAccessDeniedHttpException($entity, $field_access, '/data/relationships/' . $field_name, sprintf('The current user is not allowed to PATCH the selected field (%s).', $field_name));
    }
    // Time to save the relationship.
    foreach ($parsed_field_list as $field_item) {
      $field_list->appendItem($field_item->getValue());
    }
    $this->validate($entity);
    $entity->save();
    return $this->getRelationship($entity, $related_field, $request, 201);
  }

  /**
   * Updates the relationship of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The requested entity.
   * @param string $related_field
   *   The related field name.
   * @param mixed $parsed_field_list
   *   The entity reference field list of items to add, or a response object in
   *   case of error.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   */
  public function patchRelationship(EntityInterface $entity, $related_field, $parsed_field_list, Request $request) {
    $related_field = $this->resourceType->getInternalName($related_field);
    if ($parsed_field_list instanceof Response) {
      // This usually means that there was an error, so there is no point on
      // processing further.
      return $parsed_field_list;
    }
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $parsed_field_list */
    $this->relationshipAccess($entity, $related_field);
    // According to the specification, PATCH works a little bit different if the
    // relationship is to-one or to-many.
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field_list */
    $field_list = $entity->{$related_field};
    $is_multiple = $field_list->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->isMultiple();
    $method = $is_multiple ? 'doPatchMultipleRelationship' : 'doPatchIndividualRelationship';
    $this->{$method}($entity, $parsed_field_list);
    $this->validate($entity);
    $entity->save();
    return $this->getRelationship($entity, $related_field, $request);
  }

  /**
   * Update a to-one relationship.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The requested entity.
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $parsed_field_list
   *   The entity reference field list of items to add, or a response object in
   *   case of error.
   */
  protected function doPatchIndividualRelationship(EntityInterface $entity, EntityReferenceFieldItemListInterface $parsed_field_list) {
    if ($parsed_field_list->count() > 1) {
      throw new BadRequestHttpException(sprintf('Provide a single relationship so to-one relationship fields (%s).', $parsed_field_list->getName()));
    }
    $this->doPatchMultipleRelationship($entity, $parsed_field_list);
  }

  /**
   * Update a to-many relationship.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The requested entity.
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $parsed_field_list
   *   The entity reference field list of items to add, or a response object in
   *   case of error.
   */
  protected function doPatchMultipleRelationship(EntityInterface $entity, EntityReferenceFieldItemListInterface $parsed_field_list) {
    $field_name = $parsed_field_list->getName();
    $field_access = $parsed_field_list->access('edit', NULL, TRUE);
    if (!$field_access->isAllowed()) {
      throw new EntityAccessDeniedHttpException($entity, $field_access, '/data/relationships/' . $field_name, sprintf('The current user is not allowed to PATCH the selected field (%s).', $field_name));
    }
    $entity->{$field_name} = $parsed_field_list;
  }

  /**
   * Deletes the relationship of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The requested entity.
   * @param string $related_field
   *   The related field name.
   * @param mixed $parsed_field_list
   *   The entity reference field list of items to add, or a response object in
   *   case of error.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   */
  public function deleteRelationship(EntityInterface $entity, $related_field, $parsed_field_list, Request $request = NULL) {
    if ($parsed_field_list instanceof Response) {
      // This usually means that there was an error, so there is no point on
      // processing further.
      return $parsed_field_list;
    }
    if ($parsed_field_list instanceof Request) {
      // This usually means that there was not body provided.
      throw new BadRequestHttpException(sprintf('You need to provide a body for DELETE operations on a relationship (%s).', $related_field));
    }
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $parsed_field_list */
    $this->relationshipAccess($entity, $related_field);

    $field_name = $parsed_field_list->getName();
    $field_access = $parsed_field_list->access('edit', NULL, TRUE);
    if (!$field_access->isAllowed()) {
      throw new EntityAccessDeniedHttpException($entity, $field_access, '/data/relationships/' . $field_name, sprintf('The current user is not allowed to PATCH the selected field (%s).', $field_name));
    }
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field_list */
    $field_list = $entity->{$related_field};
    $is_multiple = $field_list->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->isMultiple();
    if (!$is_multiple) {
      throw new ConflictHttpException(sprintf('You can only DELETE from to-many relationships. %s is a to-one relationship.', $related_field));
    }

    // Compute the list of current values and remove the ones in the payload.
    $current_values = $field_list->getValue();
    $deleted_values = $parsed_field_list->getValue();
    $keep_values = array_udiff($current_values, $deleted_values, function ($first, $second) {
      return reset($first) - reset($second);
    });
    // Replace the existing field with one containing the relationships to keep.
    $entity->{$related_field} = $this->pluginManager
      ->createFieldItemList($entity, $related_field, $keep_values);

    // Save the entity and return the response object.
    $this->validate($entity);
    $entity->save();
    return $this->getRelationship($entity, $related_field, $request, 201);
  }

  /**
   * Gets a basic query for a collection.
   *
   * @param string $entity_type_id
   *   The entity type for the entity query.
   * @param array $params
   *   The parameters for the query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   A new query.
   */
  protected function getCollectionQuery($entity_type_id, $params) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);

    $query = $entity_storage->getQuery();

    // Ensure that access checking is performed on the query.
    $query->accessCheck(TRUE);

    // Compute and apply an entity query condition from the filter parameter.
    if (isset($params[Filter::KEY_NAME]) && $filter = $params[Filter::KEY_NAME]) {
      $query->condition($filter->queryCondition($query));
    }

    // Apply any sorts to the entity query.
    if (isset($params[Sort::KEY_NAME]) && $sort = $params[Sort::KEY_NAME]) {
      foreach ($sort->fields() as $field) {
        $path = $field[Sort::PATH_KEY];
        $direction = isset($field[Sort::DIRECTION_KEY]) ? $field[Sort::DIRECTION_KEY] : 'ASC';
        $langcode = isset($field[Sort::LANGUAGE_KEY]) ? $field[Sort::LANGUAGE_KEY] : NULL;
        $query->sort($path, $direction, $langcode);
      }
    }

    // Apply any pagination options to the query.
    if (isset($params[OffsetPage::KEY_NAME])) {
      $pagination = $params[OffsetPage::KEY_NAME];
    }
    else {
      $pagination = new OffsetPage(OffsetPage::DEFAULT_OFFSET, OffsetPage::SIZE_MAX);
    }
    // Add one extra element to the page to see if there are more pages needed.
    $query->range($pagination->getOffset(), $pagination->getSize() + 1);
    $query->addMetaData('pager_size', (int) $pagination->getSize());

    // Limit this query to the bundle type for this resource.
    $bundle = $this->resourceType->getBundle();
    if ($bundle && ($bundle_key = $entity_type->getKey('bundle'))) {
      $query->condition(
        $bundle_key, $bundle
      );
    }

    return $query;
  }

  /**
   * Gets a basic query for a collection count.
   *
   * @param string $entity_type_id
   *   The entity type for the entity query.
   * @param array $params
   *   The parameters for the query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   A new query.
   */
  protected function getCollectionCountQuery($entity_type_id, $params) {
    // Override the pagination parameter to get all the available results.
    unset($params[OffsetPage::KEY_NAME]);
    return $this->getCollectionQuery($entity_type_id, $params);
  }

  /**
   * Builds a response with the appropriate wrapped document.
   *
   * @param mixed $data
   *   The data to wrap.
   * @param int $response_code
   *   The response code.
   * @param array $headers
   *   An array of response headers.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   */
  protected function buildWrappedResponse($data, $response_code = 200, array $headers = []) {
    return new ResourceResponse(new JsonApiDocumentTopLevel($data), $response_code, $headers);
  }

  /**
   * Respond with an entity collection.
   *
   * @param \Drupal\jsonapi\EntityCollection $entity_collection
   *   The collection of entites.
   * @param string $entity_type_id
   *   The entity type.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   */
  protected function respondWithCollection(EntityCollection $entity_collection, $entity_type_id) {
    $response = $this->buildWrappedResponse($entity_collection);

    // When a new change to any entity in the resource happens, we cannot ensure
    // the validity of this cached list. Add the list tag to deal with that.
    $list_tag = $this->entityTypeManager->getDefinition($entity_type_id)
      ->getListCacheTags();
    $response->getCacheableMetadata()->addCacheTags($list_tag);
    return $response;
  }

  /**
   * Check the access to update the entity and the presence of a relationship.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $related_field
   *   The name of the field to check.
   */
  protected function relationshipAccess(EntityInterface $entity, $related_field) {
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $parsed_field_list */
    $entity_access = $entity->access('update', NULL, TRUE);
    if (!$entity_access->isAllowed()) {
      // @todo Is this really the right path?
      throw new EntityAccessDeniedHttpException($entity, $entity_access, $related_field, 'The current user is not allowed to update the selected resource.');
    }
    if (!($field_list = $entity->get($related_field)) || !$this->isRelationshipField($field_list)) {
      throw new NotFoundHttpException(sprintf('The relationship %s is not present in this resource.', $related_field));
    }
  }

  /**
   * Takes a field from the origin entity and puts it to the destination entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $origin
   *   The entity that contains the field values.
   * @param \Drupal\Core\Entity\EntityInterface $destination
   *   The entity that needs to be updated.
   * @param string $field_name
   *   The name of the field to extract and update.
   */
  protected function updateEntityField(EntityInterface $origin, EntityInterface $destination, $field_name) {
    // The update is different for configuration entities and content entities.
    if ($origin instanceof ContentEntityInterface && $destination instanceof ContentEntityInterface) {
      // First scenario: both are content entities.
      try {
        $field_name = $this->resourceType->getInternalName($field_name);
        $destination_field_list = $destination->get($field_name);
      }
      catch (\Exception $e) {
        throw new BadRequestHttpException(sprintf('The provided field (%s) does not exist in the entity with ID %s.', $field_name, $destination->uuid()));
      }

      $origin_field_list = $origin->get($field_name);
      if ($destination_field_list->getValue() != $origin_field_list->getValue()) {
        $field_access = $destination_field_list->access('edit', NULL, TRUE);
        if (!$field_access->isAllowed()) {
          throw new EntityAccessDeniedHttpException($destination, $field_access, '/data/attributes/' . $field_name, sprintf('The current user is not allowed to PATCH the selected field (%s).', $field_name));
        }
        $destination->{$field_name} = $origin->get($field_name);
      }
    }
    elseif ($origin instanceof ConfigEntityInterface && $destination instanceof ConfigEntityInterface) {
      // Second scenario: both are config entities.
      $destination->set($field_name, $origin->get($field_name));
    }
    else {
      throw new BadRequestHttpException('The serialized entity and the destination entity are of different types.');
    }
  }

  /**
   * Checks if is a relationship field.
   *
   * @param object $entity_field
   *   Entity definition.
   *
   * @return bool
   *   Returns TRUE, if entity field is EntityReferenceItem.
   */
  protected function isRelationshipField($entity_field) {
    $class = $this->pluginManager->getPluginClass($entity_field->getDataDefinition()->getType());
    return ($class == EntityReferenceItem::class || is_subclass_of($class, EntityReferenceItem::class));
  }

  /**
   * Build a collection of the entities to respond with and access objects.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage to load the entities from.
   * @param int[] $ids
   *   Array of entity IDs.
   *
   * @return array
   *   An array keyed by entity ID containing the keys:
   *     - entity: the loaded entity or an access exception.
   *     - access: the access object.
   */
  protected function loadEntitiesWithAccess(EntityStorageInterface $storage, $ids) {
    $output = [];
    foreach ($storage->loadMultiple($ids) as $entity) {
      $output[$entity->id()] = static::getEntityAndAccess($entity);
    }
    return $output;
  }

  /**
   * Get the object to normalize and the access based on the provided entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to test access for.
   *
   * @return array
   *   An array containing the keys:
   *     - entity: the loaded entity or an access exception.
   *     - access: the access object.
   */
  public static function getEntityAndAccess(EntityInterface $entity) {
    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
    $entity_repository = \Drupal::service('entity.repository');
    $entity = $entity_repository->getTranslationFromContext($entity, NULL, ['operation' => 'entity_upcast']);
    $access = $entity->access('view', NULL, TRUE);
    // Accumulate the cacheability metadata for the access.
    $output = [
      'access' => $access,
      'entity' => $entity,
    ];
    if ($entity instanceof AccessibleInterface && !$access->isAllowed()) {
      // Pass an exception to the list of things to normalize.
      $output['entity'] = new EntityAccessDeniedHttpException($entity, $access, '/data', 'The current user is not allowed to GET the selected resource.');
    }

    return $output;
  }

  /**
   * Checks if the given entity exists.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to test existence.
   *
   * @return bool
   *   Whether the entity already has been created.
   */
  protected function entityExists(EntityInterface $entity) {
    $entity_storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    return !empty($entity_storage->loadByProperties([
      'uuid' => $entity->uuid(),
    ]));
  }

}
