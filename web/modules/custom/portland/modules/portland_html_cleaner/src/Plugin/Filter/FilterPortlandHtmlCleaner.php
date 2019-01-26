<?php

namespace Drupal\portland_html_cleaner\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Render\FilteredMarkup;

/**
 * @Filter(
 *   id = "filter_portland_embed_classes",
 *   title = @Translation("Portland Html Cleaner"),
 *   description = @Translation("Cleans HTML in various ways for the Portland project, such as removing empty HTML elements. Replaces the HTML Purifier functionality previously used for this purpose."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class FilterPortlandHtmlCleaner extends FilterBase
{

  /**
   * Processes text in the following ways: 
   * - Removes HTML elements that are empty or only have spaces (non-breaking or otherwise), except <th>, <td>, <i>
   * - TODO: make list of elements to ignore configurable in filter form
   *
   * @param [type] $text
   * @param [type] $langcode
   * @return void
   */
  public function process($text, $langcode)
  {
    // first convert all nbsp's to spaces
    $text = html_entity_decode(Html::normalize($text));

    $result = new FilterProcessResult($text);

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    $xpath_query = "//*[matches(., '[&#x9;&#xa;&#xd; &#xa0;]+')]";

    // running these two queries in tandem seems to suit our needs, doesn't require recursion.
    $nodelist = $xpath->query("/*//*[normalize-space(.)=\"\xC2\xA0\"]");
    foreach ($nodelist as $node) {
      $node->parentNode->removeChild($node);
    }
    while (($node_list = $xpath->query("//*[not(*) and not(@*) and not(text()[normalize-space()=\"\xC2\xA0\"])]")) && $node_list->length) {
      foreach ($node_list as $node) {
        $node->parentNode->removeChild($node);
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

  function isEmpty($txt)
  {
    return preg_match('~^(?:\s+|&nbsp;)*$~iu', $txt) ? true : false;
  }
}