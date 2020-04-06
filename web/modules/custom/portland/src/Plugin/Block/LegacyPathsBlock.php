<?php
/**
 * @file
 * Contains \portland\Plugin\Block\SearchBlock
 */

namespace Drupal\portland\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\group\Entity\GroupContent;

/**
 * Provides a Portland legacy paths block.
 * 
 * This is intended as a throwaway feature. Once all content has been migrated from the
 * legacy site, it will be removed. To remove it, the following need to be deleted:
 *   - This file, src/Plugin/Block/LegacyPathsBlock.php
 *   - templates/block--portland-legacy-paths-block.html.twig
 *   - In portland.module > portland_theme, remove the portland_legacy_paths_block element
 *   - In group type config, remove field_disable_legacy_paths_block from all grop types
 *   - Remove Portland Legacy Paths Block from block layout
 *
 * @Block(
 *   id = "portland_legacy_paths_block",
 *   admin_label = @Translation("Portland Legacy Paths Block"),
 *
 * )
 */
class LegacyPathsBlock extends BlockBase {
    private static $pog_base_url = "https://www.portlandoregon.gov";
    private static $help_text = "This content page replaces the following pages on the old portlandoregon.gov website. Users on the old site will be redirected here when they hit these URLs. Use the links below to test that the legacy paths are correct. <em>This block is only visible to members of the Publishers role.</em>";

    /**
     * {@inheritdoc}
     */
    public function getCacheMaxAge() {
      return 0;
    }

    /**
     * {@inheritdoc}
     * 
     * This block only ever gets displayed in view mode, due to block placement settings,
     * and is only viewable by specific roles. If the current route is a node or group, and
     * the group's field_disable_legacy_paths_block is false, then the block is populated and displayed.
     * Otherwise, the build function returns empty array, which deactivates the block.
     */
    public function build() {

      $entity = \Drupal::routeMatch()->getParameter('node');
      $group = null;
      $enabled = false;

      if (isset($entity)) {
        // it's a node, so retrieve its parent group
        $groups = [];
        $group_contents = GroupContent::loadByEntity($entity);
        foreach ($group_contents as $group_content) {
          $groups[] = $group_content->getGroup();
        }
        // this may be a global (non-group) page; there may be no groups
        if (count($groups) > 0) {
          // assume only one group, first index
          $group = $groups[0];
        }

      } else {
        // it's not a node, so see if it's a group.
        $group = \Drupal::routeMatch()->getParameter('group');
        if (!isset($group)) {
          // it's not a group, so return false to disable the block.
          return [];
        }
        $entity = $group;
      }

      // at this point $entity is either we should have a populated group and can check whether the test links are enabled.
      // if they are not, return empty array to disable the block.
      if (!isset($group) || (isset($group->field_disable_legacy_paths_block) && $group->field_disable_legacy_paths_block->value)) {
        return [];
      }

      // if there is a node, get the legacy paths from there. otherwise, get them from the group.
      $legacy_paths = [];
      foreach ($entity->field_redirects as $redirect) {
        $value = $redirect->value;
        if (isset($value)) {
          $legacy_paths[] = $value;
        }
      }

      $render_array = [
        '#theme' => 'portland_legacy_paths_block',
        '#pog_base_url' => self::$pog_base_url,
        '#legacy_paths' => $legacy_paths,
        '#help_text' => t(self::$help_text),
      ];

      return $render_array;
    }
}