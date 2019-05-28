<?php

namespace Drupal\portland\Plugin\Condition;

use Drupal\node\NodeInterface;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides a 'Not In Group' condition.
 *
 * @Condition(
 *   id = "portland_not_in_group",
 *   label = @Translation("Portland: Not In Group"),
 *   category = @Translation("Node"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("Node")
 *     )
 *   }
 * )
 */
class NotInGroup extends RulesConditionBase {

  /**
   * When a node is created under a group, the Group modules saves the 
   * node one more time, which causes two events been triggered "create" 
   * and "update". We used this condition to skip the update event.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check for a type.
   *
   * @return bool
   *   TRUE if the group node is new.
   */
  protected function doEvaluate(NodeInterface $node) {
    return $node->hasField('field_group') && $node->get('field_group')->isEmpty();
  }
}
