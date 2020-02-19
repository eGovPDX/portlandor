<?php

namespace Drupal\media_embed_arcgis_online\Plugin\media_embed_field\Provider;

use Drupal\media_embed_field\ProviderPluginBase;

/**
 * An ArcGIS Online maps provider plugin for media embed field.
 *
 * @MediaEmbedProvider(
 *   id = "arcgis_online",
 *   title = @Translation("ArcGIS Online Maps")
 * )
 */
class ArcGISOnline extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    // Extract map URL from iframe embed code
    preg_match('/^(<style>\.embed-container [^>]+<\/style><div class="embed-container">)?<iframe [^>]+ src="\/\/[^\/]+\.maps\.arcgis\.com\/apps\/Embed\/index.html\?webmap=(?<id>[^"]+)".+<\/iframe>(<\/div>)?$/', trim($input), $matches);
    if (isset($matches['id'])) {
      return md5($matches['id']);
    }
    preg_match('/^<iframe [^>]+ src="https:\/\/arcg.is\/(?<id>[^"]+)".+<\/iframe>$/', trim($input), $matches);
    if (isset($matches['id'])) {
      return $matches['id'];
    }
    
    // Provided input didn't match any allowed format so reject it
    return FALSE;
  }

  /**
   * Get the source URL of the media from user input.
   *
   * @param string $input
   *   Input a user would enter into a media field.
   *
   * @return string
   *   The source URL of the map.
   */
  public static function getUrlFromInput($input) {
    // Extract map URL from iframe embed code
    preg_match('/^(<style>\.embed-container [^>]+<\/style><div class="embed-container">)?<iframe [^>]+ src="(?<url>\/\/[^\/]+\.maps\.arcgis\.com\/apps\/Embed\/index.html\?webmap=[^"]+)".+<\/iframe>(<\/div>)?$/', trim($input), $matches);
    if (isset($matches['url'])) {
      return $matches['url'];
    }
    preg_match('/^<iframe [^>]+ src="(?<url>https:\/\/arcg.is\/[^"]+)".+<\/iframe>$/', trim($input), $matches);
    if (isset($matches['url'])) {
      return $matches['url'];
    }

    // Provided input was URL so just return input
    return $input;
  }

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $iframe = [
      '#type' => 'media_embed_iframe',
      '#provider' => 'arcgis_online',
      '#url' => $this->getUrlFromInput($this->getInput()),
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
    // Note: Since thumbnail images are tied to maps by their embed URL, if multiple map media entities reference the same map embed URL, 
    // all maps will share the thumbnail image of the first created map media entity.

    // Load preview image
    // A bit of a hack as it just blindly references the first image field from the form POST data, but I don't know how else to do it
    $preview_image_file_id = isset($_POST['inline_entity_form']) ? $_POST['inline_entity_form']['image'][0]['fids'] : $_POST['image'][0]['fids'];  
    $preview_image = \Drupal::entityTypeManager()->getStorage('file')->load($preview_image_file_id);
    
    // Get preview image URL and transform it into a plain relative URL
    $preview_image_uri = $preview_image->getFileUri();
    $preview_image_relative_uri = file_url_transform_relative(file_create_url($preview_image_uri));

    return $GLOBALS['base_url'].$preview_image_relative_uri;
  }

}
