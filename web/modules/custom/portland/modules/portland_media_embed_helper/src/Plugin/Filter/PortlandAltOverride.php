<?php

namespace Drupal\portland_media_embed_helper\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;

#[Filter(
  id: 'portland_alt_override',
  title: new TranslatableMarkup('Portland Alt Override'),
  description: new TranslatableMarkup('Handles data-alt-override for embedded images. Also sets the alt attribute to an empty string on any embedded images with data-alt-override=true. This filter must be processed after the Display Embedded Entities filter.'),
  type: FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
)]
class PortlandAltOverride extends FilterBase {

  /**
   * @param [type] $text
   * @param [type] $langcode
   * @return void
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    if (str_contains($text, 'data-alt-override') || str_contains($text, 'data-is-decorative')) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      /** @var \DOMElement $parent_node */
      foreach ($xpath->query('//div[@data-alt-override or @data-is-decorative]') as $parent_node) {
        /** @var \DOMElement $img_node */
        $img_node = $parent_node->getElementsByTagName('img')[0];
        if (!$img_node) continue;

        // Decorative image flag sets alt text to an empty string and takes precedence over alt override.
        if ($parent_node->hasAttribute('data-is-decorative')) {
          $img_node->setAttribute('alt', '');
        } else if ($parent_node->hasAttribute('data-alt-override')) {
          $img_node->setAttribute('alt', $parent_node->getAttribute('data-alt-override'));
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
