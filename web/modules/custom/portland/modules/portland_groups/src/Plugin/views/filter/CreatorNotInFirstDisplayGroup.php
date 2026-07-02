<?php

namespace Drupal\portland_groups\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter nodes where the content author is NOT a member of the first group
 * in field_display_groups (delta 0).
 *
 * @ViewsFilter("creator_not_in_first_display_group")
 */
class CreatorNotInFirstDisplayGroup extends FilterPluginBase {

  public function adminSummary() {
    return $this->t('author is not member of first display group');
  }

  protected function operatorForm(&$form, $form_state) {}

  public function canExpose() {
    return FALSE;
  }

  public function query() {
    $this->ensureMyTable();
    $node_alias = $this->tableAlias;

    // Self-contained subquery: avoids relying on Views' join deduplication,
    // which could reuse an existing node__field_display_groups join that lacks
    // the delta=0 constraint.
    $this->query->addWhereExpression(
      0,
      "NOT EXISTS (
        SELECT 1
        FROM {node__field_display_groups} nfdg
        INNER JOIN {group_relationship_field_data} grf
          ON grf.gid = nfdg.field_display_groups_target_id
          AND grf.entity_id = $node_alias.uid
          AND grf.plugin_id = 'group_membership'
        WHERE nfdg.entity_id = $node_alias.nid
          AND nfdg.delta = 0
          AND nfdg.deleted = 0
      )"
    );
  }

}
