<?php
/**
 * @file
 * Contains \portland\Plugin\Block\SearchBlock
 */

namespace Drupal\portland\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'portland revision' block.
 *
 * @Block(
 *   id = "portland_revision_block",
 *   admin_label = @Translation("Portland Revision Block"),
 *
 * )
 */
class RevisionBlock extends BlockBase {
    private static $severity_icon = [
      'danger' => 'ban',
      'warning' => 'exclamation-triangle',
      'success' => 'check'
    ];

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

      // matches /node/XXX/revisions|latest/XXX/view
      preg_match('/\/node\/(\d+)\/*(\w+)*\/*(\d+)*\/*[(view)]*/', $current_path, $output_array);

      if(count($output_array) > 0) {
        $nid = $output_array[1];

        $node_latest_revision = self::loadLatestRevision($nid);
        $node_default_revision= \Drupal::entityTypeManager()->getStorage('node')->load($nid);
        $node_current_revision = NULL;

        if(count($output_array) == 2) {
          $node_current_revision = $node_default_revision;
        }
        elseif(count($output_array) == 3 && $output_array[2] == 'latest') {
          $node_current_revision = $node_latest_revision;
        }
        elseif(count($output_array) == 4 && $output_array[2] == 'revisions') {
          $vid = $output_array[3];
          $node_current_revision= \Drupal::entityTypeManager()->getStorage('node')->loadRevision($vid);
        }

        if($node_current_revision != NULL) {
          return self::buildRenderArray(
            $node_latest_revision,
            $node_current_revision,
            $node_default_revision
          );
        }
      }

      return array(
        '#theme' => 'portland_revision_block',
      );
    }

    public static function buildRenderArray($node_latest_revision, $node_current_revision, $node_default_revision) {
      if($node_current_revision == NULL || $node_latest_revision == NULL || $node_default_revision == NULL) return;

      $nid = $node_current_revision->nid->value;
      $node_latest_vid = $node_latest_revision->vid->value;
      $node_latest_status = $node_latest_revision->status->value;
      $node_current_vid = $node_current_revision->vid->value;
      $node_current_status = $node_current_revision->status->value;
      $node_current_is_archived = ($node_current_revision->moderation_state->value == 'archived');
      $node_default_vid = $node_default_revision->vid->value;
      $node_default_status = $node_default_revision->status->value;
      $node_rh_action = count($node_current_revision->rh_action) ? $node_current_revision->rh_action[0]->value : 'bundle_default';
      $node_rh_redirect = count($node_current_revision->rh_redirect) ? $node_current_revision->rh_redirect[0]->value : '';

      // Set the default
      $render_array = [
        '#theme' => 'portland_revision_block',
        '#alert_color' => self::getSeverity($node_current_revision),
        '#alert_icon' => self::$severity_icon[self::getSeverity($node_current_revision)],
        '#current_revision_state' => self::getModerationStateInString($node_current_revision),
        '#latest_revision_state' => [
          '#markup' => ' and new <strong>'. self::getModerationStateInString($node_latest_revision) . '</strong> revision exists.'],
        '#revision_link' => "/node/$nid/revisions/$node_latest_vid/view",
        '#revision_link_text' => 'View latest version',
        '#rabbithole_action' => self::$rh_action[$node_rh_action],
        '#rabbithole_redirect' => $node_rh_redirect,
      ];
      /*
      latest == current > default
      default is published
        (current is archived) ? DANGER : WARN, current state, NULL, view published
      default is archived
        AS_IS, current state, NULL, NULL
      */
      if($node_latest_vid == $node_current_vid && $node_current_vid > $node_default_vid) {
        if($node_default_status == 1) {
          $render_array['#alert_color'] = ($node_current_is_archived) ? 'danger' : 'warning';
          $render_array['#alert_icon'] = ($node_current_is_archived) ? 'ban' : 'exclamation-triangle';
          $render_array['#latest_revision_state'] = NULL;
          $render_array['#revision_link'] = "/node/$nid";
          $render_array['#revision_link_text'] = "View published version";
        }
        else {
          $render_array['#latest_revision_state'] = NULL;
          $render_array['#revision_link'] = NULL;
          $render_array['#revision_link_text'] = NULL;
        }
      }
      /*
      latest > current >= default
        (current is archived) ? DANGER : WARN, current state, latest state, view latest
      */
      else if($node_latest_vid > $node_current_vid && $node_current_vid >= $node_default_vid) {
        $render_array['#alert_color'] = ($node_current_is_archived) ? 'danger' : 'warning';
        $render_array['#alert_icon'] = ($node_current_is_archived) ? 'ban' : 'exclamation-triangle';
      }
      /*
      latest = current = default
        default is published
          INFO, current state
        default is archived
          DANGER, current state
      */
      else if($node_latest_vid == $node_current_vid && $node_current_vid == $node_default_vid) {
        $render_array['#latest_revision_state'] = NULL;
        $render_array['#revision_link'] = NULL;
        $render_array['#revision_link_text'] = NULL;
      }
      /*
      latest = default > current
        default is published
          (current is archived) ? DANGER : WARN, current state, latest state, view published
        default is archived
          (current is archived) ? DANGER : WARN, current state, latest state, view latest
      */
      else if($node_latest_vid == $node_default_vid && $node_default_vid > $node_current_vid ) {
        $render_array['#alert_color'] = ($node_current_is_archived) ? 'danger' : 'warning';
        $render_array['#alert_icon'] = ($node_current_is_archived) ? 'ban' : 'exclamation-triangle';
        if($node_default_status == 1) {
          $render_array['#revision_link'] = "/node/$nid";
          $render_array['#revision_link_text'] = "View published version";
        }
      }
      /*
      latest > default > current
        (current is archived) ? DANGER : WARN, current state, latest state, view latest
      */
      else if($node_latest_vid > $node_default_vid && $node_default_vid > $node_current_vid ) {
        $render_array['#alert_color'] = ($node_current_is_archived) ? 'danger' : 'warning';
        $render_array['#alert_icon'] = ($node_current_is_archived) ? 'ban' : 'exclamation-triangle';
      }

      return $render_array;
    }

    public static function getSeverity($node) {
      if($node->status->value == 0) {
        return ($node->moderation_state->value == 'archived') ? 'danger' : 'warning';
      }
      return 'success';
    }

    public static function getModerationStateInString($node) {
      $published = $node->status->value;
      $moderation_state = $node->moderation_state->value;
      if($published == "0") {
        switch($moderation_state) {
          case "archived":
            $result = "Unpublished/archived";
            break;
          case "review":
            $result = "Unpublished and in review";
            break;
          default:
            $result = "Unpublished draft";
        }
      }
      else {
        $result = "Published";
      }
      return $result;
    }

    public static function loadLatestRevision($nid) {
      // Load the latest revision
      $latestNode = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
                  ->latestRevision()
                  ->condition('nid', $nid)
                  ->accessCheck(FALSE)
                  ->execute();
      reset($latestNode);
      $latestRevisionId = key($latestNode);
      return \Drupal::entityTypeManager()->getStorage('node')->loadRevision($latestRevisionId);
    }
  }
