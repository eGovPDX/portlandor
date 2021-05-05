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
 *   description = @Translation("When enabled, applies a start cue time to YouTube and Vimeo video URLs. Requries a custom embed dialog field to capture the timecode. IMPORTANT: This filter must be processed after the Display Embedded Entities filter."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class PortlandVideoEmbedCueTime extends FilterBase {

  /**
   * @param [type] $text
   * @param [type] $langcode
   * @return void
   */
  public function process($text, $langcode)
  {
    $result = new FilterProcessResult($text);

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    // this section only gets used if data-start-cue attribute is present
    $elems = $xpath->query("//div[@data-start-cue]");
    foreach ($elems as $elem) {
      $cue = $elem->getAttribute("data-start-cue");
      $iframes = $elem->getElementsByTagName('iframe');
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
        // reassemble and set URL
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

    return $result;
  }
}