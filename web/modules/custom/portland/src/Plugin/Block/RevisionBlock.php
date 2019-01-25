<?php
/**
 * @file
 * Contains \portland\Plugin\Block\SearchBlock
 */

namespace Drupal\portland\Plugin\Block;

use Drupal\Core\Block\BlockBase;

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
      $uri_parts = explode('/', $current_path);
      $count = count($uri_parts);

      $node = NULL;
      $node_revision = NULL;
      $node_latest_revision = NULL;

      // The "latest URL is only valid when the latest revision is unpublished. " /node/221/latest"
      if($count == 4 && $uri_parts[1] == "node" && $uri_parts[3] == "latest") {
        $node_latest_revision = self::loadLatestRevision($uri_parts[2]);
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($uri_parts[2]);
        $node_status = $node->status->value;
        if($node_latest_revision->status->value == 0) {
          // If the node is published
          if ($node_status == 1) {
            $revision_link = '/node/'.$uri_parts[2];
            $revision_description = 'View published version';
          }

          // Warn the editor that she's viewing an unpublished version
          return array(
            '#theme' => 'portland_revision_block',
            '#current_revision_state' => self::getModerationStateInString($node_latest_revision),
            '#severity' => 'warning',
            '#revision_link' => $revision_link,
            '#revision_description' => $revision_description
          );
        }
      }
      // Load the default revision of the node. "/node/221"
      else if($count == 3 && $uri_parts[1] == "node") {
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($uri_parts[2]);
        $node_status = $node->status->value;
        $node_latest_revision = self::loadLatestRevision($uri_parts[2]);
      }
      
      // Load the revision if it's specified in URL. "/node/221/revisions/3669/view"
      else if ($count == 6 && $uri_parts[3] == "revisions" && $uri_parts[5] == "view") {
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($uri_parts[2]);
        $node_status = $node->status->value;

        $node = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($uri_parts[4]);
        //$node = \Drupal::service('entity.repository')->getTranslationFromContext($node);
        $node_latest_revision = self::loadLatestRevision($uri_parts[2]);
      }

      // If the current node revision is not the latest. Show the block.
      if($node && $node_latest_revision) {
        // If this is the latest revision
        if($node->vid->value == $node_latest_revision->vid->value) {
          // If the node is published
          $revision_link = NULL;
          $revision_description = NULL;
          // if ($node_status == 1) {
          //   $revision_link = '/node/'.$uri_parts[2];
          //   $revision_description = 'View published version';
          // }
          
          // Warn the editor that she's viewing an unpublished version
          return array(
            '#theme' => 'portland_revision_block',
            '#current_revision_state' => self::getModerationStateInString($node), // . ".  This is the Latest version.",
            '#severity' => self::getSeverity($node_latest_revision),
            '#revision_link' => $revision_link,
            '#revision_description' => $revision_description
          );
        }
        else {
          // If the node is published
          if ($node_status == 1) {
            $revision_link = '/node/'.$uri_parts[2];
            $revision_description = 'View published version';
          }
          else {
            $revision_link = '/node/'.$uri_parts[2].'/revisions/'.$node_latest_revision->vid->value.'/view';
            $revision_description = 'View latest version';
          }
          // The URL "/node/221/latest" is only valid when the node is Draft or Review
          $latestUrlPart = ($node_latest_revision->status->value == 0) ? '/revisions/'.$node_latest_revision->vid->value.'/view' : '';
          return array(
            '#theme' => 'portland_revision_block',
            '#current_revision_state' => self::getModerationStateInString($node),
            '#description' => ' and new <strong>'. self::getModerationStateInString($node_latest_revision) . '</strong> revision exists.',
            '#severity' => self::getSeverity($node),
            '#revision_link' => $revision_link,
            '#revision_description' => $revision_description
          );
        }
      }

      return array(
        '#theme' => 'portland_revision_block',
        '#description' => NULL
      );
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
            $result = "In review";
            break;
          default:
            $result = "Draft";
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
                  ->execute();
      reset($latestNode);
      $latestRevisionId = key($latestNode);
      return \Drupal::entityTypeManager()->getStorage('node')->loadRevision($latestRevisionId);
    }
  }