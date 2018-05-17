<?php

namespace Drupal\openapi_redoc\Controller;

use Drupal\Core\Url;

/**
 * Provides callback for generating docs page.
 */
class DocController {

  /**
   * Generates the doc page.
   *
   * @param string $api_module
   *   The API module.
   *
   * @return array
   *   A render array.
   */
  public function generateDocs($api_module) {
    $options = \Drupal::request()->get('options', []);
    $build = [
      '#theme' => 'redoc',
      '#openapi_url' => Url::fromRoute("openapi.$api_module", [], ['query' => ['_format' => 'json', 'options' => $options]])->setAbsolute()->toString(),
    ];
    return $build;
  }

  /**
   * Gets the page title.
   *
   * @param string $api_module
   *   The API module.
   *
   * @return string
   *   The title.
   */
  public function getTitle($api_module) {
    $options = \Drupal::request()->get('options', []);
    $title = '';
    // @todo Support $options in title.
    if ($api_module === 'jsonapi') {
      $title = 'JSON API documentation';
    }
    elseif ($api_module === 'rest') {
      $title = 'REST API documentation';
    }
    return $title;
  }

}
