<?php

namespace Drupal\simple_oauth\Entities;

use Drupal\Core\Cache\CacheableDependencyInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

/**
 * Extends the scope entity to include a name and description.
 */
interface ScopeEntityNameInterface extends ScopeEntityInterface, CacheableDependencyInterface {

  /**
   * Returns a name for the scope.
   *
   * @return string
   *   The name of the scope.
   */
  public function getName();

  /**
   * Returns a description for the scope.
   *
   * @return string|null
   *   The description of the scope.
   */
  public function getDescription();

}
