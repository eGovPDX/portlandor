<?php

namespace Drupal\openapi_swagger_ui\Controller;

/**
 * Swagger UI controller for JSON API documentation.
 */
class SwaggerUIJsonApiController extends SwaggerUIControllerBase {

  /**
   * {@inheritdoc}
   */
  protected function getJsonGeneratorRoute() {
    return 'openapi.jsonapi';
  }

}
