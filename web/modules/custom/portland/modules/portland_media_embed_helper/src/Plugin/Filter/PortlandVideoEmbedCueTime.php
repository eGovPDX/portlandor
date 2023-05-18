<?php

namespace Drupal\portland_media_embed_helper\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Render\FilteredMarkup;

/**
 * @Filter(
 *   id = "portland_video_embed_cue_time",
 *   title = @Translation("Portland Video Embed Cue Time"),
 *   description = @Translation("When enabled, convert the <code>data-start-cue</code> attribute value into the start cue time for YouTube and Vimeo video URLs. Requries a custom embed dialog field to capture the timecode. This filter must be processed after the Display Embedded Entities filter."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class PortlandVideoEmbedCueTime extends FilterBase {

  /**
   * @param [type] $text
   * @param [type] $langcode
   * @return void
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, 'data-entity-uuid') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//*[@data-entity-uuid]') as $node) {
        // Check if it's a video
        $uuid = $node->getAttribute('data-entity-uuid');
        if (empty($uuid)) continue;

        $entity_loaded_by_uuid = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['uuid' => $uuid]);
        if (count($entity_loaded_by_uuid) == 0) continue;
        $entity_loaded_by_uuid = reset($entity_loaded_by_uuid);
        if ($entity_loaded_by_uuid->bundle() != 'video') continue;

        // Read the data-start-cue attribute's value, then delete it.
        // HACK ALERT: Entity Embed dialog no longer allows data-start-cue attribute, so we've hijacked the
        // title attribute and are using it to store the value now.
        $cue = $node->getAttribute('title');
        $node->removeAttribute('title');

        if (empty($cue)) continue;
        $iframes = $node->getElementsByTagName('iframe');
        foreach ($iframes as $frame) {
          $src = $frame->getAttribute('src');
          $url = parse_url($src);
          parse_str($url['query'], $query);
          if (strpos($url['host'], 'youtube') !== false) {
            $query['start'] = $cue;
            $url['query'] = http_build_query($query);
          } else if (strpos($url['host'], 'vimeo') !== false) {
            $url['path'] .= "#t=" . $cue . "s";
          }
          
          // Reassemble and set URL
          $src = $url['scheme'] . '://' . $url['host'] . $url['path'] . '?' . $url['query'];
          $frame->setAttribute('src', $src);
        }
      }

      $result->setProcessedText(Html::serialize($dom))
        ->addAttachments([
          'library' => [
            'filter/caption',
          ],
        ]);
    }

    return $result;
  }
}