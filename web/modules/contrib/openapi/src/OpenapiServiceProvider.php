<?php

namespace Drupal\openapi;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service provider that creators OpenAPI generator services.
 */
class OpenapiServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $modules = $container->getParameter(('container.modules'));
    // @todo Add 'openapi_generator' tag? Can you just make up tags?
    if (isset($modules['rest'])) {
      $container->register('openapi.generator.rest', 'Drupal\openapi\OpenApiGenerator\OpenApiRestGenerator')
        ->addArgument(new Reference('entity_type.manager'))
        ->addArgument(new Reference('router.route_provider'))
        ->addArgument(new Reference('entity_field.manager'))
        ->addArgument(new Reference('schemata.schema_factory'))
        ->addArgument(new Reference('serializer'))
        ->addArgument(new Reference('plugin.manager.rest'));
    }
    if (isset($modules['jsonapi'])) {
      $container->register('openapi.generator.jsonapi', 'Drupal\openapi\OpenApiGenerator\OpenApiJsonapiGenerator')
        ->addArgument(new Reference('entity_type.manager'))
        ->addArgument(new Reference('router.route_provider'))
        ->addArgument(new Reference('entity_field.manager'))
        ->addArgument(new Reference('schemata.schema_factory'))
        ->addArgument(new Reference('serializer'));
    }
  }

}
