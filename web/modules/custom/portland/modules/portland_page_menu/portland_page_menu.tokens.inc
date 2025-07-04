<?php

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\entity_reference_hierarchy\EntityReferenceHierarchyFieldItemList;

/**
 * Implements hook_token_info().
 */
function portland_page_menu_token_info() {
  $info = [];

  $info['tokens']['node']['portland-menu-path'] = [
    'name' => t('Portland menu path'),
    'description' => 'The path to the parents of the current node in the page menu (e.g. grandparent/parent)',
  ];

  return $info;
}

/**
 * Given a child delta, find the parent deltas in the outline. For example:
 * 0
 * 1
 *   2 ($my_delta)
 *   3
 * 4
 * returns: [1]
 * 
 * @param array $delta_outline The outline of the entity reference hierarchy field.
 * @param int $my_delta The delta of the child node to find parents for.
 * @return array An array of deltas for the parents of $my_delta.
 */
function _get_parent_deltas_in_outline(array $delta_outline, int $my_delta): array {
  $values = [];
  foreach ($delta_outline as $delta => $branch) {
    if (!empty($branch['children'])) {
      if (array_key_exists($my_delta, $branch['children'])) {
        $values[] = $delta;
      }

      $values = array_merge($values, _get_parent_deltas_in_outline($branch['children'], $my_delta));
    }
  }
  
  return $values;
}

/** 
 * Given a node ID, find the menu link text for each parent of that node (not including the top-level parent that contains the menu itself).
 * 
 * @param EntityReferenceHierarchyFieldItemList $items The entity reference hierarchy field item list.
 * @param array $token_options The token options.
 * @param int $current_nid The current node ID to find parents for.
 * @return array An array of cleaned menu link text values for the parent nodes.
 */
function _get_parent_menu_link_text_array(EntityReferenceHierarchyFieldItemList $items, int $current_nid): array {
  $delta_outline = $items->getFieldHierarchyOutline();
  $values = [];
  $my_delta = array_key_first(array_filter($items->getValue(), fn($item) => $item['target_id'] == $current_nid));
  if (is_null($my_delta)) return [];
  
  $parent_deltas = _get_parent_deltas_in_outline($delta_outline, $my_delta);
  foreach ($parent_deltas as $delta) {
    $node = $items[$delta]->entity;
    if ($node) {
      $values[] = $node->field_menu_link_text->value;
    }
  }

  return $values;
}

/**
 * Implements hook_tokens().
 */
function portland_page_menu_tokens($type, $tokens, array $data = array(), array $options = array(), BubbleableMetadata $bubbleable_metadata = null) {
  $replacements = [];

  if ($type == 'node') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'portland-menu-path':
          if (array_key_exists('node', $data) && $data['node'] instanceof \Drupal\node\NodeInterface) {
            $node = $data['node'];
            $parent_node = $node->field_parent->entity;
            if ($parent_node) {
              /** @var EntityReferenceHierarchyFieldItemList $items */
              $items = $parent_node->field_menu;
              $parent_paths = array_merge([$parent_node->field_menu_link_text->value], _get_parent_menu_link_text_array($items, $node->id()));
              $replacements[$original] = implode('/', array_map(fn($val) => \Drupal::service('pathauto.alias_cleaner')->cleanString($val, $options), $parent_paths));
              break;
            }
          }

          $replacements[$original] = '';
          break;
      }
    }
  }

  return $replacements;
}
