<?php

namespace Drupal\portland_content_completion\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\group\Entity\GroupContent;

/**
 * Provides a Portland content completion block. This block displays a table of groups
 * and the status of each group's content: total, published, and % published.
 * 
 * Building this in views was not possible without creating really convoluted joins that
 * killed performance to the point of timing out, so it's a custom database query.
 * The downside of that is that we can't easily leverage all the views functionality
 * such as filtering, sorting and exporting.
 * 
 * @Block(
 *   id = "portland_content_completion_block2",
 *   admin_label = @Translation("Portland Content Competion Block"),
 *
 * )
 */
class PortlandContentCompletionBlock extends BlockBase {
  // private static $help_text = "[Help text for Content Completion Block]";

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   * 
   */
  public function build() {

    // if we're on a group page, get the id and filter completion report for just that group
    $group = \Drupal::routeMatch()->getParameter('group');
    $where_clause = "";

    if (isset($group)) {
      $gid = $group->id();
      $where_clause = "WHERE FD.id = $gid";
    }
    
    $dbConn = \Drupal::database();
    $query = "SELECT * FROM (SELECT distinct FD.id, label,
                (SELECT COUNT(NFD.status)
                FROM group_content_field_data CFD 
                INNER JOIN node_field_data NFD ON CFD.entity_id = NFD.nid
                WHERE CFD.gid = FD.id) AS 'Total',
                (SELECT COUNT(NFD.status)
                FROM group_content_field_data CFD 
                INNER JOIN node_field_data NFD ON CFD.entity_id = NFD.nid
                WHERE CFD.gid = FD.id and NFD.status = 1) AS 'Published',
                ROUND((SELECT Published) / (SELECT Total) * 100) AS 'Complete'
              FROM groups_field_data FD
              $where_clause
              ORDER BY label) a";
    $query = $dbConn->query($query);
    $result = $query->fetchAll();

    $render_array = [
      '#theme' => 'portland_content_completion_block2',
      '#completion_table' => $result
    ];

    return $render_array;
  }

}