<?php

namespace Drupal\portland_relations\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for defining Relation entities.
 *
 * @ingroup portland_relations
 */
interface RelationInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Relation creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Relation.
   */
  public function getCreatedTime();

  /**
   * Sets the Relation creation timestamp.
   *
   * @param int $timestamp
   *   The Relation creation timestamp.
   *
   * @return \Drupal\portland_relations\Entity\RelationInterface
   *   The called Relation entity.
   */
  public function setCreatedTime($timestamp);

}
