<?php

namespace Drupal\portland_groups\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter nodes where:
 *   1. The node has more than one group in field_display_groups, AND
 *   2. The content author is a member of at least one of those groups.
 *
 * @ViewsFilter("creator_not_in_first_display_group")
 */
class CreatorNotInFirstDisplayGroup extends FilterPluginBase {

  public function adminSummary() {
    return $this->t('has multiple display groups and author is missing from at least one');
  }

  protected function operatorForm(&$form, $form_state) {}

  public function canExpose() {
    return FALSE;
  }

  public function query() {
    $this->ensureMyTable();
    $node_alias = $this->tableAlias;

    // Condition 1 (cheap pre-filter): only nodes with more than one display group.
    // Condition 2: at least one of those groups does NOT have the author as a member.
    $this->query->addWhereExpression(
      0,
      "(SELECT COUNT(*) FROM {node__field_display_groups}
        WHERE entity_id = $node_alias.nid AND deleted = 0) > 1
      AND EXISTS (
        SELECT 1
        FROM {node__field_display_groups} nfdg
        LEFT JOIN {group_relationship_field_data} grf
          ON grf.gid = nfdg.field_display_groups_target_id
          AND grf.entity_id = $node_alias.uid
          AND grf.plugin_id = 'group_membership'
        WHERE nfdg.entity_id = $node_alias.nid
          AND nfdg.deleted = 0
          AND grf.id IS NULL
      )"
    );
  }

}
