<?php

namespace Drupal\portland_relations\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * Provides an interface for defining Relation type entities.
 */
interface RelationTypeInterface extends ConfigEntityInterface, RevisionableEntityBundleInterface {

  // Add get/set methods for your configuration properties here.
}
