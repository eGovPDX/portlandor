<?php

namespace Drupal\portland;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Service provider for the Portland module.
 */
class PortlandServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Swap in a subclass that matches SAML authnames case-insensitively.
    // This has to be a class swap (not service decoration) because
    // samlauth's SamlService type-hints its $authmap argument against the
    // concrete Drupal\externalauth\Authmap class rather than
    // AuthmapInterface, so a decorator that only implements the interface
    // fails that type check.
    if ($container->hasDefinition('externalauth.authmap')) {
      $container->getDefinition('externalauth.authmap')
        ->setClass('Drupal\portland\ExternalAuth\CaseInsensitiveSamlAuthmap');
    }
  }

}
