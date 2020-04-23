<?php

namespace Drupal\portland_content_completion\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\group\Entity\GroupContent;
use Drupal\portland_content_completion\Controller\PortlandContentCompletionController;

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
    $gid = NULL;

    if (isset($group)) {
      $gid = $group->id();
    }
    
    $dbConn = \Drupal::database();
    $query = PortlandContentCompletionController::completionQuery($gid);

    $query = $dbConn->query($query);
    $result = $query->fetchAll();

    $render_array = [
      '#theme' => 'portland_content_completion_block2',
      '#completion_table' => $result
    ];

    return $render_array;
  }

}