<?php

namespace Drupal\portland_media_embed_helper\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Render\FilteredMarkup;

/**
 * @Filter(
 *   id = "portland_media_embed_html_filter",
 *   title = @Translation("Portland Media Embed HTML Filter"),
 *   description = @Translation("Removes HTML elements that only contain non breaking spaces. IMPORTANT: This filter must be run after the Embed Media filter from Media Library."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class PortlandMediaEmbedHtmlFilter extends FilterBase {

  /**
   * @param [type] $text
   * @param [type] $langcode
   * @return void
   */
  public function process($text, $langcode)
  {
    $result = new FilterProcessResult($text);

    // this query was found at https://stackoverflow.com/questions/11744454/xpath-to-recursively-remove-empty-dom-nodes
    // it cleans up empty DOM elements, nested empty elements, elements with non-breaking spaces, etc.,
    // but ignores elements with legitimate content. it also leaves the following un-closed elements: img, br, hr, drupal-entity
    // HOWEVER, it also removes empty table cells, so we'll need to modify this if we ever decide to allow tables in content.
    $query = "//*[not(normalize-space((translate(., '\xC2\xA0\', ''))))
                and
                  not(descendant-or-self::*[self::img or self::input or self::br or self::hr or self::drupal-entity])
                  ]
                  [not(ancestor::*
                          [count(.| //*[not(normalize-space((translate(., '\xC2\xA0\', ''))))
                                      and
                                        not(descendant-or-self::*
                                                [self::img or self::input or self::br or self::hr or self::drupal-entity])
                                        ]
                                  )
                          =
                            count(//*[not(normalize-space((translate(., '\xC2\xA0\', ''))))
                                    and
                                      not(descendant-or-self::*
                                              [self::img or self::input or self::br or self::hr or self::drupal-entity])
                                      ]
                                )
                            ]
                        )
                  ]";
    //$query = "//*[text()=\"\xC2\xA0\"]";

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    $elems = $xpath->query($query);
    foreach ($elems as $elem) {
      $elem->parentNode->removeChild($elem);
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