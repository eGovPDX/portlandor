<?php

namespace Drupal\openapi\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller class for returning JSON API OpenAPI specification.
 */
class JsonApiSpecificationController extends ApiSpecificationControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('openapi.generator.jsonapi'),
      $container->get('request_stack')
    );
  }

}
