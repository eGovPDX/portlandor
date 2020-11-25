<?php

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
  private static $severity_icon = [
    'danger' => 'ban',
    'warning' => 'exclamation-triangle',
    'success' => 'check',
  ];

  private static $rh_action = [
    'bundle_default' => '',
    'access_denied' => '\'access denied\'',
    'display_page' => '\'display page\'',
    'page_not_found' => '\'page not found\'',
    'page_redirect' => 'URL',
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
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $output_array = [];

    // Matches /node/XXX/revisions|latest/XXX/view
    preg_match('/\/node\/(\d+)\/*(\w+)*\/*(\d+)*\/*[(view)]*/', $current_path, $output_array);

    if (count($output_array) > 0) {
      $nid = $output_array[1];

      $node_latest_revision = self::loadLatestRevision($nid, $langcode);
      $node_default_revision = self::loadDefaultRevision($nid, $langcode);
      $node_current_revision = NULL;

      if (count($output_array) == 2) {
        // i.e. /node/XXX
        $node_current_revision = $node_default_revision;
      }
      elseif (count($output_array) == 3 && $output_array[2] == 'latest') {
        // i.e. /node/XXX/latest
        $node_current_revision = $node_latest_revision;
      }
      elseif (count($output_array) == 4 && $output_array[2] == 'revisions') {
        // i.e. /node/XXX/revisions/XXX/view
        $vid = $output_array[3];
        $node_current_revision = \Drupal::entityTypeManager()->getStorage('node')->loadRevision($vid)->getTranslation($langcode);
      }

      if ($node_current_revision != NULL) {
        return self::buildRenderArray(
          $node_latest_revision,
          $node_current_revision,
          $node_default_revision,
        );
      }
    }

    return array(
      '#theme' => 'portland_revision_block',
    );
  }


  /**
   * Build the render array that will be returned from the build() function.
   *
   * @param Drupal\node\Entity $node_latest_revision
   *   The node's latest revision for the current langauge.
   * @param Drupal\node\Entity $node_current_revision
   *   The node's current revision for the current langauge.
   * @param Drupal\node\Entity $node_default_revision
   *   The node's default revision for the current language.
   * 
   * @return
   *   An associative array containing all of the render elements.
   */
  private static function buildRenderArray($node_latest_revision, $node_current_revision, $node_default_revision) {
    if ($node_current_revision == NULL || $node_latest_revision == NULL || $node_default_revision == NULL) return;

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
    if ($node_latest_vid == $node_current_vid && $node_current_vid > $node_default_vid) {
      if ($node_default_status == 1) {
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
    elseif ($node_latest_vid > $node_current_vid && $node_current_vid >= $node_default_vid) {
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
    elseif ($node_latest_vid == $node_current_vid && $node_current_vid == $node_default_vid) {
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
    elseif ($node_latest_vid == $node_default_vid && $node_default_vid > $node_current_vid ) {
      $render_array['#alert_color'] = ($node_current_is_archived) ? 'danger' : 'warning';
      $render_array['#alert_icon'] = ($node_current_is_archived) ? 'ban' : 'exclamation-triangle';
      if ($node_default_status == 1) {
        $render_array['#revision_link'] = "/node/$nid";
        $render_array['#revision_link_text'] = "View published version";
      }
    }
    /*
    latest > default > current
      (current is archived) ? DANGER : WARN, current state, latest state, view latest
    */
    elseif ($node_latest_vid > $node_default_vid && $node_default_vid > $node_current_vid ) {
      $render_array['#alert_color'] = ($node_current_is_archived) ? 'danger' : 'warning';
      $render_array['#alert_icon'] = ($node_current_is_archived) ? 'ban' : 'exclamation-triangle';
    }

    return $render_array;
  }


  /**
   * Returns a node's severity code.
   *
   * @param int $node
   *   The node entity.
   * 
   * @return
   *   A string identifier corresponding to the severity level associated with a node's moderation state.
   */
  private static function getSeverity($node) {
    if ($node->status->value == 0) {
      return ($node->moderation_state->value == 'archived') ? 'danger' : 'warning';
    }
    return 'success';
  }


  /**
   * Returns a node's moderation state in string format.
   *
   * @param int $node
   *   The node entity.
   * 
   * @return
   *   A string description of the moderation state
   */
  private static function getModerationStateInString($node) {
    $published = $node->status->value;
    $moderation_state = $node->moderation_state->value;
    
    if ($published == "0") {
      switch ($moderation_state) {
        case "archived":
          $result = "Unpublished/archived";
          break;

        case "review":
          $result = "Unpublished and in review";
          break;

        default:
          $result = "Unpublished draft";
      }
    } else {
      $result = "Published";
    }

    return $result;
  }


  /**
   * Loads the latest revision of a node for the specified language.
   *
   * @param int $nid
   *   The node ID.
   * @param string $langcode
   *   The language code.
   * 
   * @return Drupal\node\Entity
   */
  private static function loadLatestRevision($nid, $langcode) {
    $latestRevisionId = \Drupal::entityTypeManager()->getStorage('node')->getLatestTranslationAffectedRevisionId($nid, $langcode);

    if ($latestRevisionId == NULL) {
      return NULL;
    } else {
      return \Drupal::entityTypeManager()->getStorage('node')->loadRevision($latestRevisionId)->getTranslation($langcode);
    }
  }


  /**
   * Loads the default revision of a node for the specified language.
   * The default revision is the published revision, if any, otherwise it's the latest revision.
   *
   * @param int $nid
   *   The node ID.
   * @param string $langcode
   *   The language code.
   *
   * @return Drupal\node\Entity
   */ 
  private static function loadDefaultRevision($nid, $langcode) {
    // Load the latest published revision in specified language (if any)
    $publishedRevision = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
                      ->allRevisions()
                      ->condition('nid', $nid)
                      ->condition('revision_translation_affected', 1, '=', $langcode)
                      ->condition('revision_default', 1)
                      ->range(0, 1)
                      ->sort('vid', 'DESC')
                      ->accessCheck(FALSE)
                      ->execute();
    reset($publishedRevision);
    $publishedRevisionId = key($publishedRevision);
    
    if ($publishedRevisionId == NULL) {
      // If no published revision exists, just return latest revision in specified language
      return self::loadLatestRevision($nid, $langcode);
    } else {
      // Return the published revision
      return \Drupal::entityTypeManager()->getStorage('node')->loadRevision($publishedRevisionId);
    }
  }
}
