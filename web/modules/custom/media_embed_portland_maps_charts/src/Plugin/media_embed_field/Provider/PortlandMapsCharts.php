<?php

namespace Drupal\media_embed_portland_maps_charts\Plugin\media_embed_field\Provider;

use Drupal\media_embed_field\ProviderPluginBase;

/**
 * An Portland Maps Charts provider plugin for media embed field.
 *
 * @MediaEmbedProvider(
 *   id = "portland_maps_charts",
 *   title = @Translation("Portland Maps Charts")
 * )
 */
class PortlandMapsCharts extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    // Extract chart URL from iframe embed code
    preg_match('/^https:\/\/www\.portlandmaps\.com(?<id>[-_\/[:alnum:]]*\/charts\/.*)$/', trim($input), $matches);
    if (isset($matches['id'])) {
      return md5($matches['id']);
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
   *   The source URL of the chart.
   */
  public static function getUrlFromInput($input) {
    // Provided input was URL so just return input
    return $input;
  }

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $iframe = [
      '#type' => 'media_embed_iframe',
      '#provider' => 'portland_maps_charts',
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
    // Note: Since thumbnail images are tied to charts by their embed URL, if multiple chart media entities reference the same chart embed URL, 
    // all charts will share the thumbnail image of the first created chart media entity.

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
