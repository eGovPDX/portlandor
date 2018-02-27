<?php

namespace Drupal\jsonapi\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 */
class EntryPoint extends ControllerBase {

  /**
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * EntryPoint constructor.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The resource type repository.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(ResourceTypeRepositoryInterface $resource_type_repository, RendererInterface $renderer) {
    $this->resourceTypeRepository = $resource_type_repository;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jsonapi.resource_type.repository'),
      $container->get('renderer')
    );
  }

  /**
   * Controller to list all the resources.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   */
  public function index() {
    // Execute the request in context so the cacheable metadata from the entity
    // grants system is caught and added to the response. This is surfaced when
    // executing the underlying entity query.
    $context = new RenderContext();
    /** @var \Drupal\Core\Cache\CacheableResponseInterface $response */
    $do_build_urls = function () {
      $self = Url::fromRoute('jsonapi.resource_list')->setAbsolute();

      return array_reduce($this->resourceTypeRepository->all(), function (array $carry, ResourceType $resource_type) {
        // TODO: Learn how to invalidate the cache for this page when a new entity
        // type or bundle gets added, removed or updated.
        // $this->response->addCacheableDependency($definition);
        $url = Url::fromRoute(sprintf('jsonapi.%s.collection', $resource_type->getTypeName()))
          ->setAbsolute();
        $carry[$resource_type->getTypeName()] = $url->toString();

        return $carry;
      }, ['self' => $self->toString()]);
    };
    $urls = $this->renderer->executeInRenderContext($context, $do_build_urls);

    $json_response = new CacheableJsonResponse([
      'data' => [],
      'links' => $urls,
    ]
    );

    if (!$context->isEmpty()) {
      $json_response->addCacheableDependency($context->pop());
    }

    return $json_response;
  }

}
