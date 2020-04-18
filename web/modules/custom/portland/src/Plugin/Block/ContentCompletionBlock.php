<?php

namespace Drupal\portland\Plugin\Block;

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
 *   id = "portland_content_completion_block",
 *   admin_label = @Translation("Portland Content Competion Block"),
 *
 * )
 */
class ContentCompletionBlock extends BlockBase {
  private static $help_text = "[Help text for Content Completion Block]";

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

    $dbConn = \Drupal::database();
    $query = "SELECT * FROM (SELECT distinct label,
                (SELECT count(NFD.status)
                FROM group_content_field_data CFD 
                INNER JOIN node_field_data NFD ON CFD.entity_id = NFD.nid
                WHERE CFD.gid = FD.id) AS 'Total',
                (SELECT count(NFD.status)
                FROM group_content_field_data CFD 
                INNER JOIN node_field_data NFD ON CFD.entity_id = NFD.nid
                WHERE CFD.gid = FD.id and NFD.status = 1) AS 'Published',
                concat(round((SELECT Published) / (SELECT Total) * 100, 2), '%') AS '% Complete'
              FROM groups_field_data FD
              ORDER BY label) a";
    $query = $dbConn->query($query);
    $result = $query->fetchAll();

    $render_array = [
      '#theme' => 'portland_content_completion_block',
      '#completion_table' => $result,
      '#test_var' => '#####',
      '#help_text' => t(self::$help_text),
    ];

    return $render_array;
  }

}