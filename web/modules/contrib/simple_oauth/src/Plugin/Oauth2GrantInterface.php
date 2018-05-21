<?php

namespace Drupal\simple_oauth\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines an interface for OAuth2 Grant plugins.
 */
interface Oauth2GrantInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Gets the grant object.
   *
   * @param League\OAuth2\Server\Grant\GrantTypeInterface
   *   The grant type.
   */
  public function getGrantType();

}
