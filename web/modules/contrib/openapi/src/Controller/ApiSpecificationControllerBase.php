<?php

namespace Drupal\openapi\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\openapi\OpenApiGenerator\OpenApiGeneratorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * API Specification controller base.
 */
abstract class ApiSpecificationControllerBase implements ContainerInjectionInterface {

  /**
   * The OpenAPI generator.
   *
   * @var \Drupal\openapi\OpenApiGenerator\OpenApiGeneratorInterface
   */
  protected $generator;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * JsonApiSpecificationController constructor.
   *
   * @param \Drupal\openapi\OpenApiGenerator\OpenApiGeneratorInterface $generator
   *   The OpenAPI generator.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request.
   */
  public function __construct(OpenApiGeneratorInterface $generator, RequestStack $request) {
    $this->generator = $generator;
    $this->request = $request;
  }

  /**
   * Gets the OpenAPI output in JSON format.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function getSpecification() {
    $options = $this->request->getCurrentRequest()->get('options', []);
    $spec = $this->generator->getSpecification($options);
    return new JsonResponse($spec);
  }

}
