<?php

namespace Drupal\portland_content_completion\Controller;

use Drupal\Core\Controller\ControllerBase;
// use Drupal\Core\Entity\EntityFormBuilderInterface;
// use Drupal\Core\Entity\EntityTypeManagerInterface;
// use Drupal\Core\Render\RendererInterface;
// use Drupal\group\Entity\Controller\GroupContentController;
// use Drupal\group\Entity\GroupInterface;
// use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
// use Drupal\user\PrivateTempStoreFactory;
// use Symfony\Component\DependencyInjection\ContainerInterface;

// use Drupal\Core\Config\Entity;
// use Symfony\Component\HttpKernel;
// use Drupal\Core\Plugin;

/**
 * Creates a Content Completion admin report page
 */
class PortlandContentCompletionController extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function contentCompletionPage() {

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
              ORDER BY label) a";
    $query = $dbConn->query($query);
    $result = $query->fetchAll();

    $markup = 

    $render_array = [
      'portland_content_completion_page' => [
        '#theme' => 'portland_content_completion_page',
        '#completion_table' => $result,
        '#test_var' => '#####'
      ]
    ];

    return $render_array;
  }

}