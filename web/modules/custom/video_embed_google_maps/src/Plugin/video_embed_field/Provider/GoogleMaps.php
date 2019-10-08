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
    //return "https://upload.wikimedia.org/wikipedia/commons/c/ca/1x1.png";

    // Note: Since thumbnail images are tied to maps by their embed URL, if multiple map media entities reference the same map embed URL, 
    // all maps will share the thumbnail image of the first created map media entity.

    // Load preview image
    $preview_image_file_id = $_POST['image'][0]['fids'];  // A bit of a hack as it just blindly references first image field from POST data, but I don't know how else to do it
    $preview_image = \Drupal::entityTypeManager()->getStorage('file')->load($preview_image_file_id);
    
    // Get preview image URL and transform it into a plain relative URL
    $preview_image_uri = $preview_image->getFileUri();
    $preview_image_relative_uri = file_url_transform_relative(file_create_url($preview_image_uri));

    return $preview_image_relative_uri;
    //$GLOBALS['base_url'] . file_url_transform_relative(file_create_url( \Drupal::entityTypeManager()->getStorage('file')->load($_POST['image'][0]['fids'])->getFileUri() ));
  }

}
