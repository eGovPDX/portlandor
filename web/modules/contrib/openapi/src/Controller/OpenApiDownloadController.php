<?php

namespace Drupal\openapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Lists OpenAPI direct downloads.
 *
 * @todo Should this just be menu items?
 */
class OpenApiDownloadController extends ControllerBase {

  /**
   * List all REST Doc pages.
   */
  public function downloadsList() {
    $return['direct_download'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . $this->t('OpenAPI files') . '</h2>' .
        // @todo Which page should the docs link to?
        '<p>' . $this->t('The following links provide the REST or JSON API resources documented in <a href=":open_api_spec">OpenAPI(fka Swagger)</a> format.', [':open_api_spec' => 'https://github.com/OAI/OpenAPI-Specification/tree/OpenAPI.next']) . ' ' .
        $this->t('This JSON file can be used in tools such as the <a href=":swagger_editor">Swagger Editor</a> to provide a more detailed version of the API documentation.', [':swagger_editor' => 'http://editor.swagger.io/#/']) . '</p>',
    ];
    $open_api_links = [];

    if ($this->moduleHandler()->moduleExists('rest')) {
      $open_api_links['entities'] = [
        'url' => Url::fromRoute('openapi.rest', [], ['query' => ['_format' => 'json']]),
        'title' => $this->t('Open API: REST Entities'),
      ];
    }

    if ($this->moduleHandler()->moduleExists('jsonapi')) {
      $open_api_links['jsonapi'] = [
        'url' => Url::fromRoute('openapi.jsonapi', [], ['query' => ['_format' => 'json']]),
        'title' => $this->t('Open API: JSON API'),
      ];
    }

    // @todo create link non-entity rest downloads.
    $return['direct_download']['links'] = [
      '#theme' => 'links',
      '#links' => $open_api_links,
    ];
    return $return;
  }

}
