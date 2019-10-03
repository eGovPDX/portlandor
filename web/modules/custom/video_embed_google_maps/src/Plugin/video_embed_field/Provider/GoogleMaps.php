<?php

namespace Drupal\video_embed_google_maps\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * A Google Maps provider plugin for video embed field.
 *
 * @VideoEmbedProvider(
 *   id = "google_maps",
 *   title = @Translation("Google Maps")
 * )
 */
class GoogleMaps extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    // Extract map URL from iframe embed code
    // This regex allows the user to paste in the entire embed HTML code, but it's too long for the field datatype in the database...
    // preg_match('/^<iframe src="https?:\/\/www\.google\.com\/maps\/embed\?pb=(?<id>[^"]+)".+<\/iframe>$/', $input, $matches);
    // return isset($matches['id']) ? $matches['id'] : FALSE;
    
    // User is required to extract map embed URL from HTML code

    // Standard Google Maps embed URL
    preg_match('/^https?:\/\/www\.google\.com\/maps\/embed\?pb=(?<id>.+)$/', $input, $matches);
    if (isset($matches['id'])) {
      return $matches['id'];
    }

    // Google My Maps embed URL
    preg_match('/^https?:\/\/www\.google\.com\/maps\/d\/embed\?mid=(?<id>.+)$/', $input, $matches);
    if (isset($matches['id'])) {
      return $matches['id'];
    }

    // Provided URL didn't match any allowed format so reject it
    return FALSE;

    // TODO:
    // Do we need to MD5 hash the ID so that it can be used in filenames?
  }

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $iframe = [
      '#type' => 'video_embed_iframe',
      '#provider' => 'google_maps',
      '#url' => $this->input,
      '#query' => [],
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => '',
        'style' => 'border:0;'
      ],
    ];
    return $iframe;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    //$url = 'https://drive.google.com/thumbnail?authuser=0&sz=w320&id=%s';
    //return sprintf($url, $this->getVideoId());
    return "https://upload.wikimedia.org/wikipedia/commons/c/ca/1x1.png";
  }

}
