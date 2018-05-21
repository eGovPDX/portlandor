<?php

namespace Drupal\simple_oauth\Entities;

use League\OAuth2\Server\Entities\ClientEntityInterface as LeagueClientEntityInterface;

interface ClientEntityInterface extends LeagueClientEntityInterface {

  /**
   * Set the name of the client.
   *
   * @param string $name
   *   The name to set.
   */
  public function setName($name);

  /**
   * Returns the associated Drupal entity.
   *
   * @return \Drupal\consumers\Entity\Consumer
   *   The Drupal entity.
   */
  public function getDrupalEntity();

}
