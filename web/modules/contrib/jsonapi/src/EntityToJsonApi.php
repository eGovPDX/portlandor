<?php

namespace Drupal\jsonapi;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\jsonapi\Resource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Simplifies the process of generating a JSON API version of an entity.
 *
 * @api
 */
class EntityToJsonApi {

  /**
   * The currently logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Serializer object.
   *
   * @var \Drupal\jsonapi\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The JSON API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * EntityToJsonApi constructor.
   *
   * @param \Drupal\jsonapi\Serializer\Serializer $serializer
   *   The serializer.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The resource type repository.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The currently logged in user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(Serializer $serializer, ResourceTypeRepositoryInterface $resource_type_repository, AccountInterface $current_user, RequestStack $request_stack) {
    $this->serializer = $serializer;
    $this->resourceTypeRepository = $resource_type_repository;
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
  }

  /**
   * Return the requested entity as a raw string.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to generate the JSON from.
   *
   * @return string
   *   The raw JSON string of the requested resource.
   */
  public function serialize(EntityInterface $entity) {
    // TODO: Supporting includes requires adding the 'include' query string.
    return $this->serializer->serialize(new JsonApiDocumentTopLevel($entity),
      'api_json',
      $this->calculateContext($entity)
    );
  }

  /**
   * Return the requested entity as an structured array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to generate the JSON from.
   *
   * @return array
   *   The JSON structure of the requested resource.
   */
  public function normalize(EntityInterface $entity) {
    return $this->serializer->normalize(new JsonApiDocumentTopLevel($entity),
      'api_json',
      $this->calculateContext($entity)
    );
  }

  /**
   * Calculate the context for the serialize/normalize operation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to generate the JSON from.
   *
   * @return array
   *   The context.
   */
  protected function calculateContext(EntityInterface $entity) {
    // TODO: Supporting includes requires adding the 'include' query string.
    $path_prefix = $this->resourceTypeRepository->getPathPrefix();
    $resource_type = $this->resourceTypeRepository->get(
      $entity->getEntityTypeId(),
      $entity->bundle()
    );
    $resource_path = $resource_type->getPath();
    $path = sprintf('/%s/%s/%s', $path_prefix, $resource_path, $entity->uuid());
    $master_request = $this->requestStack->getMasterRequest();
    $request = Request::create($master_request->getSchemeAndHttpHost() . $master_request->getBaseUrl() . $path, 'GET');
    return [
      'account' => $this->currentUser,
      'cacheable_metadata' => new CacheableMetadata(),
      'resource_type' => $resource_type,
      'request' => $request,
    ];
  }

}
