<?php

namespace Drupal\portland\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\media_remote\Plugin\Field\FieldFormatter\MediaRemoteFormatterBase;

/**
 * Plugin implementation of the 'media_iframe_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "media_iframe_embed",
 *   label = @Translation("Remote Media - Iframe Embed"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class MediaIframeEmbedFormatter extends MediaRemoteFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function getUrlRegexPattern() {
    $patterns = [
      // ArcGIS
      '^https?:\/\/arcg\.is\/(.*)$',
      '^https?:\/\/www\.arcgis\.com\/(.*)$',
      // Google Maps
      "^https?:\/\/www\.google\.com\/maps\/embed(.*)$",
      "^https?:\/\/www\.google\.com\/maps\/d\/embed(.*)$",
      // PortlandMaps map or chart
      "^https?:\/\/(.*)\.portlandmaps\.com\/(.*)$",
      "^https?:\/\/pdx\.maps\.arcgis\.com\/(.*)$",
      // POG
      '^https?:\/\/www\.portlandoregon\.gov\/bes\/bigpipe\/\w+\.cfm$',
      // Tableau
      '^https?:\/\/(online|public)\.tableau\.com\/(.*)$',
      // Smartsheet form
      "^https?:\/\/app\.smartsheet\.com\/b\/form\/.+$",
      // Smartsheet publish
      "^https?:\/\/publish\.smartsheet\.com\/.+$",
      // RankedVote
      "^https:\/\/app.rankedvote.co\/rv\/[^\/]+\/vote\/embed-rv\/?$",
    ];

    return "/" . join("|", $patterns) . "/";
  }

  /**
   * {@inheritdoc}
   */
  public static function getValidUrlExampleStrings(): array {
    return ['Only supports embeddable links from arcg.is, arcgis.com, pdx.maps.arcgis.com, rankedvote.co, Google Maps, PortlandMaps.com, PortlandOregon.gov, Tableau, and Smartsheet (links starting with app.smartsheet.com/b/form or publish.smartsheet.com). If you would like to request a new service, please contact website@portlandoregon.gov for review.'];
  }

  /**
   * {@inheritdoc}
   */
  public static function deriveMediaDefaultNameFromUrl($url) {
    return hash('md5', $url);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      if ($item->isEmpty()) {
        continue;
      }
      $matches = [];
      $pattern = static::getUrlRegexPattern();
      preg_match($pattern, $item->value, $matches);
      if (empty($matches[0])) {
        continue;
      }
      $elements[$delta] = [
        '#type' => 'html_tag',
        '#tag' => 'iframe',
        '#attributes' => [
          'src' =>  $item->value,
          'width' => '100%',
          'height' => '100%',
          'frameborder' => '0',
          'allowfullscreen' => 'true',
        ],
      ];
    }
    return $elements;
  }
}
