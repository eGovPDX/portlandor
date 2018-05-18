<?php

namespace Drupal\jsonapi\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter for upcasting JSON API resource type names to objects.
 *
 * @internal
 */
class ResourceTypeConverter implements ParamConverterInterface {

  /**
   * The JSON API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * ResourceTypeConverter constructor.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The JSON API resource type repository.
   */
  public function __construct(ResourceTypeRepositoryInterface $resource_type_repository) {
    $this->resourceTypeRepository = $resource_type_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    return $this->resourceTypeRepository->getByTypeName($value);
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] === 'jsonapi_resource_type');
  }

}
