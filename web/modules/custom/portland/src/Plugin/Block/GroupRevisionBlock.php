<?php

namespace Drupal\portland\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use \Drupal\content_moderation\Entity\ContentModerationState;

/**
 * Provides a 'portland group revision' block.
 *
 * @Block(
 *   id = "portland_group_revision_block",
 *   admin_label = @Translation("Portland Group Revision Block"),
 * )
 */
class GroupRevisionBlock extends BlockBase {
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

    // Matches /group/XXX/revisions|latest/XXX/view
    preg_match('/\/group\/(\d+)\/*(\w+)*\/*(\d+)*\/*[(view)]*/', $current_path, $output_array);

    if (count($output_array) > 0) {
      $gid = $output_array[1];

      $group_latest_revision = self::loadLatestRevision($gid, $langcode);
      $group_default_revision = self::loadDefaultRevision($gid, $langcode);
      $group_current_revision = NULL;

      if (count($output_array) == 2) {
        // i.e. /group/XXX
        $group_current_revision = $group_default_revision;
      }
      elseif (count($output_array) == 3 && $output_array[2] == 'latest') {
        // i.e. /group/XXX/latest
        $group_current_revision = $group_latest_revision;
      }
      elseif (count($output_array) == 4 && $output_array[2] == 'revisions') {
        // i.e. /group/XXX/revisions/XXX/view
        $vid = $output_array[3];
        $group_current_revision = \Drupal::entityTypeManager()->getStorage('group')->loadRevision($vid)->getTranslation($langcode);
      }

      if ($group_current_revision != NULL) {
        return self::buildRenderArray(
          $group_latest_revision,
          $group_current_revision,
          $group_default_revision,
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
   * @param Drupal\group\Entity\Group $group_latest_revision
   *   The group's latest revision for the current langauge.
   * @param Drupal\group\Entity\Group $group_current_revision
   *   The group's current revision for the current langauge.
   * @param Drupal\group\Entity\Group $group_default_revision
   *   The group's default revision for the current language.
   *
   * @return
   *   An associative array containing all of the render elements.
   */
  private static function buildRenderArray($group_latest_revision, $group_current_revision, $group_default_revision) {
    if ($group_current_revision == NULL || $group_latest_revision == NULL || $group_default_revision == NULL) return;

    $gid = $group_current_revision->id->value;
    $group_latest_vid = $group_latest_revision->revision_id->value;
    $group_current_vid = $group_current_revision->revision_id->value;
    $group_current_is_archived = ($group_current_revision->moderation_state->value === 'archived');
    $group_default_vid = $group_default_revision->revision_id->value;
    $group_default_published = $group_default_revision->isPublished();
    $group_rh_action = count($group_current_revision->rh_action) ? $group_current_revision->rh_action[0]->value : 'bundle_default';
    $group_rh_redirect = count($group_current_revision->rh_redirect) ? $group_current_revision->rh_redirect[0]->value : '';
    $moderation_state_str = self::getModerationStateDescription($group_current_revision);

    // Set the default
    $render_array = [
      '#theme' => 'portland_revision_block',
      '#alert_color' => 'success',
      '#alert_icon' => 'check',
      '#current_revision_state' => t('This revision is') . " $moderation_state_str.",
      '#revision_link' => "/group/$gid/latest",
      '#revision_link_text' => t('View latest version'),
      '#rabbithole_action' => self::$rh_action[$group_rh_action],
      '#rabbithole_redirect' => $group_rh_redirect,
    ];

    // If viewing the default revision
    if ($group_current_vid == $group_default_vid) {
      $render_array['#alert_color'] = $group_default_published ? 'success' : 'danger';
      $render_array['#alert_icon'] = $group_default_published ? 'check' : 'ban';

      $has_newer_revision = $group_current_vid < $group_latest_vid;
      // If published and no newer revision, hide revision block
      if ($group_default_published && !$has_newer_revision) {
        return [];
      }

      if ($has_newer_revision) {
        $render_array['#alert_color'] = 'warning';
        $render_array['#alert_icon'] = 'exclamation-triangle';
      } else {
        $render_array['#revision_link'] = NULL;
        $render_array['#revision_link_text'] = NULL;
      }

      $render_array['#current_revision_state'] = [
        '#markup' => '<strong>'
                      . ($group_default_published ? t('Published') : t('Unpublished'))
                      . '</strong>' . ($has_newer_revision ? t(', although a newer <strong>@state</strong> revision exists', [ '@state' => self::getModerationStateLabel($group_latest_revision) ]) : '') . '. '
                      . ($has_newer_revision ? '' : t('This group homepage is @state.', [ '@state' => $moderation_state_str ]))
      ];
    }
    // If latest revision is newer than current revision
    elseif ($group_current_vid < $group_latest_vid) {
      $render_array['#alert_color'] = 'warning';
      $render_array['#alert_icon'] = 'exclamation-triangle';
      $render_array['#current_revision_state'] = t('You are viewing an outdated revision.');
    }
    // If not viewing default revision
    elseif ($group_current_vid != $group_default_vid) {
      $render_array['#alert_color'] = 'warning';
      $render_array['#alert_icon'] = 'exclamation-triangle';
      if ($group_default_published) {
        $render_array['#revision_link'] = "/group/$gid";
        $render_array['#revision_link_text'] = t('View published version');
      }
    }

    return $render_array;
  }

  /**
   * Returns a group's moderation state label.
   *
   * @param int $group
   *   The group entity.
   *
   * @return
   *   The label of the moderation state
   */
  private static function getModerationStateLabel($group) {
    $content_moderation_state = ContentModerationState::loadFromModeratedEntity($group);
    $state_machine_name = $content_moderation_state->get('moderation_state')->value;
    $workflow = $content_moderation_state->get('workflow')->entity;
    $state_label = $workflow->get('type_settings')['states'][$state_machine_name]['label'];

    return t($state_label);
  }

  /**
   * Returns a group's moderation state description text for display to the user.
   *
   * @param int $group
   *   The group entity.
   *
   * @return
   *   A string description of the moderation state
   */
  private static function getModerationStateDescription($group) {
    $moderation_state = $group->moderation_state->value;

    switch ($moderation_state) {
      case "review":
        $result = "unpublished and in review";
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
   * Loads the latest revision of a group for the specified language.
   *
   * @param int $gid
   *   The group ID.
   * @param string $langcode
   *   The language code.
   *
   * @return Drupal\group\Entity\Group
   */
  private static function loadLatestRevision($gid, $langcode) {
    $latestRevisionId = \Drupal::entityTypeManager()->getStorage('group')->getLatestTranslationAffectedRevisionId($gid, $langcode);

    if ($latestRevisionId == NULL) {
      return NULL;
    } else {
      return \Drupal::entityTypeManager()->getStorage('group')->loadRevision($latestRevisionId)->getTranslation($langcode);
    }
  }


  /**
   * Loads the default revision of a group for the specified language.
   *
   * @param int $gid
   *   The group ID.
   * @param string $langcode
   *   The language code.
   *
   * @return Drupal\group\Entity\Group
   */
  private static function loadDefaultRevision($gid, $langcode) {
    // Load the default revision in specified language (if any)
    $defaultRevision = \Drupal::entityTypeManager()->getStorage('group')->getQuery()
                      ->allRevisions()
                      ->condition('id', $gid)
                      ->condition('revision_translation_affected', 1, '=', $langcode)
                      ->condition('revision_default', 1)
                      ->range(0, 1)
                      ->sort('revision_id', 'DESC')
                      ->accessCheck(FALSE)
                      ->execute();
    reset($defaultRevision);
    $defaultRevisionId = key($defaultRevision);

    if ($defaultRevisionId == NULL) {
      return NULL;
    } else {
      return \Drupal::entityTypeManager()->getStorage('group')->loadRevision($defaultRevisionId)->getTranslation($langcode);
    }
  }
}
