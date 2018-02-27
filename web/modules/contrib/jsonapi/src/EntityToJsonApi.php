<?php

namespace Drupal\jsonapi;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\jsonapi\Resource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Serializer;

/**
 * Simplifies the process of generating a JSON API version of an entity.
 */
class EntityToJsonApi {

  /**
   * The currently logged in user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Serializer object.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * EntityToJsonApi constructor.
   *
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The currently logged in user.
   */
  public function __construct(Serializer $serializer, ResourceTypeRepositoryInterface $resource_type_repository, AccountProxyInterface $current_user) {
    $this->serializer = $serializer;
    $this->resourceTypeRepository = $resource_type_repository;
    $this->currentUser = $current_user;
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
    $request = new Request();
    return [
      'account' => $this->currentUser,
      'cacheable_metadata' => new CacheableMetadata(),
      'resource_type' => $this->resourceTypeRepository->get(
        $entity->getEntityTypeId(),
        $entity->bundle()
      ),
      'request' => $request,
    ];
  }

}
