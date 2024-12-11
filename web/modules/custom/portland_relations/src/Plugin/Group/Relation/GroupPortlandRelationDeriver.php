<?php

namespace Drupal\portland_relations\Plugin\Group\Relation;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;
use Drupal\portland_relations\Entity\RelationType;

/**
 * Derives plugins based on node type.
 */
class GroupPortlandRelationDeriver extends DeriverBase {
  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    assert($base_plugin_definition instanceof GroupRelationTypeInterface);
    $this->derivatives = [];

    foreach (RelationType::loadMultiple() as $name => $relation_type) {
      $label = $relation_type->label();

      $this->derivatives[$name] = clone $base_plugin_definition;
      $this->derivatives[$name]->set('entity_bundle', $name);
      $this->derivatives[$name]->set('label', t('Group Portland Relation (@type)', ['@type' => $label]));
      $this->derivatives[$name]->set('description', t('Adds %type relations to groups both publicly and privately.', ['%type' => $label]));
    }

    return $this->derivatives;
  }
}
