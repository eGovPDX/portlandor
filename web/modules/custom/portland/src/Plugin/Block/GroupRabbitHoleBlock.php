<?php
/**
 * @file
 * Contains \portland\Plugin\Block\SearchBlock
 */

namespace Drupal\portland\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'portland group rabbit hole' block.
 *
 * @Block(
 *   id = "portland_group_rabbithole_block",
 *   admin_label = @Translation("Portland Group Rabbit Hole Block"),
 *
 * )
 */
class GroupRabbitHoleBlock extends BlockBase {
  private static $rh_action = [
    'bundle_default' => '',
    'access_denied' => '\'access denied\'',
    'display_page' => '\'display page\'',
    'page_not_found' => '\'page not found\'',
    'page_redirect' => 'URL'
  ];

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_path = \Drupal::service('path.current')->getPath();

    $output_array = [];

    // matches /group/XXX
    preg_match('/\/group\/(\d+)$/', $current_path, $output_array);

    if(count($output_array) > 0) {
      $gid = $output_array[1];
      $group = \Drupal::entityTypeManager()->getStorage('group')->load($gid);
      
      return self::buildRenderArray($group);
    }

    return array(
      '#theme' => 'portland_group_rabbithole_block',
    );
  }

  public static function buildRenderArray($group) {
    if($group == NULL) return;

    $node_rh_action = count($group->rh_action) ? $group->rh_action[0]->value : 'bundle_default';
    $node_rh_redirect = count($group->rh_redirect) ? $group->rh_redirect[0]->value : '';

    // Set the default
    $render_array = [
      '#theme' => 'portland_group_rabbithole_block',
      '#rabbithole_action' => self::$rh_action[$node_rh_action],
      '#rabbithole_redirect' => $node_rh_redirect,
    ];

    return $render_array;
  }

}
