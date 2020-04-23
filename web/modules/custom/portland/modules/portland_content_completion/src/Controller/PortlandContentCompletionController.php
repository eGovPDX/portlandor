<?php

namespace Drupal\portland_content_completion\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Creates a Content Completion admin report page
 */
class PortlandContentCompletionController extends ControllerBase {

  // this list of group content plugin types has to be created manually, since it only exists in the database as config,
  // not as queryable data. this list is taken from the Admin:Group:Content (group_nodes) view. our content completion
  // report should match what's in that view.
  public static $types = "'advisory_group-group_node-event', 'advisory_group-group_node-news', 'advisory_group-group_node-page', 
              'bureau_office-group_node-contact', 'bureau_office-group_node-event', 'bureau_office-group_node-news', 'bureau_office-group_node-page', 
              'elected_official-group_node-news', 'elected_official-group_node-page', 'group_content_type_25772c2545f68', 'group_content_type_287d9f4c07b28', 
              'group_content_type_308c5896d9c88', 'group_content_type_441c83afa3bc9', 'group_content_type_579e0d54dbc75', 'group_content_type_719e85d55bb96', 
              'group_content_type_787ad02856c62', 'group_content_type_89af0b5360a22', 'group_content_type_ab415a72b1aa0', 'group_content_type_ab6b4e6568ca8', 
              'group_content_type_b5ac30f6ec4e7', 'group_content_type_ba2512a3d9225', 'group_content_type_c088a2077a0c7', 'group_content_type_d2d3068587f58', 
              'group_content_type_d77c73ef74ef0', 'group_content_type_da42b77877243', 'group_content_type_db5b25211b313', 'group_content_type_e9b37d80f513a', 
              'group_content_type_ec1fc1b4137da', 'group_content_type_f8c21c1e82c21', 'program-group_node-city_service', 'program-group_node-contact', 
              'program-group_node-data_table', 'program-group_node-event', 'program-group_node-news', 'program-group_node-notification', 
              'program-group_node-page', 'project-group_node-contact', 'project-group_node-data_table', 'project-group_node-event', 'project-group_node-news', 
              'project-group_node-notification', 'project-group_node-page'";

  /**
   * Returns a page that displays a full list of groups and their percentage of completed content.
   *
   * @return array
   *   A simple renderable array.
   */
  public function contentCompletionPage() {

    $dbConn = \Drupal::database();
    $query = PortlandContentCompletionController::completionQuery(FALSE);
    $query = $dbConn->query($query);
    $result = $query->fetchAll();

    $markup = 

    $render_array = [
      'portland_content_completion_page' => [
        '#theme' => 'portland_content_completion_page',
        '#completion_table' => $result
      ]
    ];

    return $render_array;
  }

  /**
   * Returns the sql query needed to generate the completion report.
   * 
   */
  public static function completionQuery($gid) {

    $where_clause = "";
    if ($gid) {
      $where_clause = "WHERE FD.id = $gid";
    }

    $types = self::$types;
    $query = "
      select * from (
        select distinct
          FD.id, FD.label, FD2.label as 'Parent', FD2.id as 'ParentId',
          (
            select count(N.nid)
            from node N 
            inner join node_field_data NFD on N.nid = NFD.nid
            inner join group_content_field_data CFD on N.nid = CFD.entity_id
            inner join groups_field_data GFD on CFD.gid = GFD.id
            where CFD.gid = FD.id
              and CFD.type IN ($types)
          ) as 'Total',
          (
            select count(N.nid)
            from node N 
            inner join node_field_data NFD on N.nid = NFD.nid
            inner join group_content_field_data CFD on N.nid = CFD.entity_id
            inner join groups_field_data GFD on CFD.gid = GFD.id
            where CFD.gid = FD.id and NFD.status = 1
              and CFD.type IN ($types)
          ) as 'Published', ROUND((SELECT Published) / (SELECT Total) * 100) AS 'Complete'
        from groups_field_data FD
        left join group__field_parent_group PG on FD.id = PG.entity_id
        left join groups_field_data FD2 on PG.field_parent_group_target_id = FD2.id
        $where_clause
        order by Total desc, label
      ) a;
    ";

    return $query;

  }

}