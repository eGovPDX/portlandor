<?php

namespace Drupal\openapi\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller class for returning RESTs OpenAPI specification.
 */
class RestSpecificationController extends ApiSpecificationControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openapi.generator.rest'),
      $container->get('request_stack')
    );
  }

}
