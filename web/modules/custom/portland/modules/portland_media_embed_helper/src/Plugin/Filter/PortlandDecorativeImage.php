<?php

namespace Drupal\portland_media_embed_helper\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * @Filter(
 *   id = "portland_decorative_image",
 *   title = @Translation("Portland Decorative Image"),
*   description = @Translation("Sets the alt attribute to an empty string on any embedded images with data-is-decorative=true. This filter must be processed after the Display Embedded Entities filter."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 * )
 */
class PortlandDecorativeImage extends FilterBase {

  /**
   * @param [type] $text
   * @param [type] $langcode
   * @return void
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (str_contains($text, 'data-is-decorative')) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//div[@data-is-decorative]//img') as $node) {
        $node->setAttribute('alt', '');
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
