<?php

namespace Drupal\portland\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use \Drupal\content_moderation\Entity\ContentModerationState;

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
    $node_current_vid = $node_current_revision->vid->value;
    $node_current_is_archived = ($node_current_revision->moderation_state->value == 'archived');
    $node_default_vid = $node_default_revision->vid->value;
    $node_default_published = $node_default_revision->isPublished();
    $node_rh_action = count($node_current_revision->rh_action) ? $node_current_revision->rh_action[0]->value : 'bundle_default';
    $node_rh_redirect = count($node_current_revision->rh_redirect) ? $node_current_revision->rh_redirect[0]->value : '';
    $moderation_state_str = self::getModerationStateDescription($node_current_revision);

    // Set the default
    $render_array = [
      '#theme' => 'portland_revision_block',
      '#alert_color' => 'success',
      '#alert_icon' => 'check',
      '#current_revision_state' => t('This revision is') . " $moderation_state_str.",
      '#revision_link' => "/node/$nid/revisions/$node_latest_vid/view",
      '#revision_link_text' => t('View latest version'),
      '#rabbithole_action' => self::$rh_action[$node_rh_action],
      '#rabbithole_redirect' => $node_rh_redirect,
    ];

    // If viewing the default revision
    if ($node_current_vid == $node_default_vid) {
      $render_array['#alert_color'] = $node_default_published ? 'success' : 'danger';
      $render_array['#alert_icon'] = $node_default_published ? 'check' : 'ban';

      // If a newer revision exists
      $has_newer_revision = $node_current_vid < $node_latest_vid;
      if ($has_newer_revision) {
        $render_array['#alert_color'] = 'warning';
        $render_array['#alert_icon'] = 'exclamation-triangle';
      } else {
        $render_array['#revision_link'] = NULL;
        $render_array['#revision_link_text'] = NULL;
      }

      $render_array['#current_revision_state'] = [
        '#markup' => '<strong>'
                      . ($node_default_published ? t('Published') : t('Unpublished'))
                      . '</strong>' . ($has_newer_revision ? t(', although a newer <strong>@state</strong> revision exists', [ '@state' => self::getModerationStateLabel($node_latest_revision) ]) : '') . '. '
                      . ($has_newer_revision ? '' : t('This page is @state.', [ '@state' => $moderation_state_str ]))
      ];
    }
    // If latest revision is newer than current revision
    elseif ($node_current_vid < $node_latest_vid) {
      $render_array['#alert_color'] = 'warning';
      $render_array['#alert_icon'] = 'exclamation-triangle';
      $render_array['#current_revision_state'] = t('You are viewing an outdated revision.');
    }
    // If not viewing default revision
    elseif ($node_current_vid != $node_default_vid) {
      $render_array['#alert_color'] = 'warning';
      $render_array['#alert_icon'] = 'exclamation-triangle';
      if ($node_default_published) {
        $render_array['#revision_link'] = "/node/$nid";
        $render_array['#revision_link_text'] = t('View published version');
      }
    }

    return $render_array;
  }

  /**
   * Returns a node's moderation state label.
   *
   * @param int $node
   *   The node entity.
   *
   * @return
   *   The label of the moderation state
   */
  private static function getModerationStateLabel($node) {
    $content_moderation_state = ContentModerationState::loadFromModeratedEntity($node);
    $state_machine_name = $content_moderation_state->get('moderation_state')->value;
    $workflow = $content_moderation_state->get('workflow')->entity;
    $state_label = $workflow->get('type_settings')['states'][$state_machine_name]['label'];

    return t($state_label);
  }

  /**
   * Returns a node's moderation state description text for display to the user.
   *
   * @param int $node
   *   The node entity.
   *
   * @return
   *   A string description of the moderation state
   */
  private static function getModerationStateDescription($node) {
    $moderation_state = $node->moderation_state->value;

    switch ($moderation_state) {
      case "attorney_review":
        $result = "unpublished and waiting for attorney review";
        break;

      case "budget_office_review":
        $result = "unpublished and waiting for budget office review";
        break;

      case "bureau_review":
        $result = "unpublished and waiting for bureau review";
        break;

      case "council_clerk_review":
        $result = "unpublished and waiting for council clerk review";
        break;

      case "elected_review":
        $result = "unpublished and waiting for elected review";
        break;

      case "policy_editor_review":
        $result = "unpublished and waiting for policy editor review";
        break;

      case "review":
        $result = "unpublished and in review";
        break;

      case "published":
        $result = "visible to the public";
        break;

      case "archived":
      case "unpublished":
        $result = "not visible to the public";
        break;

      default:
        $result = "an unpublished draft";
    }

    return t($result);
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
   *
   * @param int $nid
   *   The node ID.
   * @param string $langcode
   *   The language code.
   *
   * @return Drupal\node\Entity
   */
  private static function loadDefaultRevision($nid, $langcode) {
    // Load the default revision in specified language (if any)
    $defaultRevision = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
                      ->allRevisions()
                      ->condition('nid', $nid)
                      ->condition('revision_translation_affected', 1, '=', $langcode)
                      ->condition('revision_default', 1)
                      ->range(0, 1)
                      ->sort('vid', 'DESC')
                      ->accessCheck(FALSE)
                      ->execute();
    reset($defaultRevision);
    $defaultRevisionId = key($defaultRevision);

    if ($defaultRevisionId == NULL) {
      return NULL;
    } else {
      return \Drupal::entityTypeManager()->getStorage('node')->loadRevision($defaultRevisionId)->getTranslation($langcode);
    }
  }
}
