<?php

namespace Drupal\media_embed_tableau\Plugin\media_embed_field\Provider;

use Drupal\media_embed_field\ProviderPluginBase;

/**
 * A Tableau provider plugin for media embed field.
 *
 * @MediaEmbedProvider(
 *   id = "tableau",
 *   title = @Translation("Tableau")
 * )
 */
class Tableau extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    // Extract chart URL from iframe embed code
    preg_match('/^<iframe src="https?:\/\/(online|public)\.tableau\.com\/(?<id>[^"]+\?[^"]*:embed=(true|yes|y|1)[^"]*)".+<\/iframe>$/', trim($input), $matches);
    if (isset($matches['id'])) {
      return md5($matches['id']);
    }
    
    // Standard Tableau embed URL
    preg_match('/^https?:\/\/(online|public)\.tableau\.com\/(?<id>[^"]+\?[^"]*:embed=(true|yes|y|1)[^"]*)$/', trim($input), $matches);
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
    // Extract chart URL from iframe embed code
    preg_match('/^<iframe src="(?<url>https?:\/\/(online|public)\.tableau\.com\/[^"]+\?[^"]*:embed=(true|yes|y|1)[^"]*)".+<\/iframe>$/', trim($input), $matches);
    if (isset($matches['url'])) {
      return $matches['url'];
    }
    
    // Provided input was URL so just return input
    return $input;
  }

  /**
   * Get the width of the media from user input.
   *
   * @param string $input
   *   Input a user would enter into a media field.
   *
   * @param string $default_width
   *   Default width if not provided in the user input.
   *
   * @return string
   *   The width of the chart.
   */
  public static function getWidthFromInput($input, $default_width) {
    // Extract chart width from input URL
    preg_match('/(?|&)width=(?<width>\d+)/', trim($input), $matches);
    if (isset($matches['width'])) {
      return $matches['width'];
    }
    
    // Width was not provided in the input so just return default width
    return $default_width;
  }

  /**
   * Get the height of the media from user input.
   *
   * @param string $input
   *   Input a user would enter into a media field.
   *
   * @param string $default_height
   *   Default height if not provided in the user input.
   *
   * @return string
   *   The height of the chart.
   */
  public static function getHeightFromInput($input, $default_height) {
    // Extract chart height from input URL
    preg_match('/(?|&)height=(?<height>\d+)/', trim($input), $matches);
    if (isset($matches['height'])) {
      return $matches['height'];
    }
    
    // Height was not provided in the input so just return default height
    return $default_height;
  }

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $iframe = [
      '#type' => 'media_embed_iframe',
      '#provider' => 'tableau',
      '#url' => $this->getUrlFromInput($this->getInput()),
      '#query' => [],
      '#attributes' => [
        'width' => $this->getWidthFromInput($this->getInput(), $width),
        'height' => $this->getHeightFromInput($this->getInput(), $height),
        'frameborder' => '0',
        'allowfullscreen' => 'true',
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
