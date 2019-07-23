<?php

namespace Drupal\portland\Plugin\Condition;

use Drupal\node\NodeInterface;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides a 'In Review And Has Reviwer' condition.
 *
 * @Condition(
 *   id = "portland_in_review_and_has_reviewer",
 *   label = @Translation("Portland: In Review And Has Reviwer"),
 *   category = @Translation("Node"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("Node")
 *     )
 *   }
 * )
 */
class InReviewAndHasReviwer extends RulesConditionBase {

  /**
   * Check if a node is of a specific set of types.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check for a type.
   *
   * @return bool
   *   TRUE if the node is In Review and has a reviewer.
   */
  protected function doEvaluate(NodeInterface $node) {
    // Verify that
    // 1. the node has moderation state as "review"
    // 2. the node has a reviewer
    if( $node->hasField('moderation_state') && ($node->moderation_state->value === 'review')
    && $node->hasField('field_reviewer') && (! $node->get('field_reviewer')->isEmpty()) )
      return true;

    return false;
  }

}
