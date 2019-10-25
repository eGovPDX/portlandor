<?php

namespace Drupal\portland_relations\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Relation entities.
 */
class RelationViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
