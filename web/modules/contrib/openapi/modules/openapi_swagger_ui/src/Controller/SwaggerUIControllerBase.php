<?php

namespace Drupal\openapi_swagger_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller for the Swagger UI page callbacks.
 */
abstract class SwaggerUIControllerBase extends ControllerBase {

  protected $request;

  /**
   * Constructs a new SwaggerController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RequestStack $request) {
    $this->entityTypeManager = $entity_type_manager;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * Creates render array for documentation page for a given resource url.
   *
   * @param \Drupal\Core\Url $json_url
   *   The resource file needed to create the documentation page.
   *
   * @return array
   *   The render array.
   */
  protected function swaggerUi(Url $json_url) {
    $query = [
      '_format' => 'json',
    ];
    if ($options = $this->request->getCurrentRequest()->get('options', [])) {
      $query['options'] = $options;
    }

    $json_url->setOption('query', $query);

    $build = [
      '#theme' => 'swagger_ui',
      '#attached' => [
        'library' => [
          'openapi_swagger_ui/swagger_ui_integration',
          'openapi_swagger_ui/swagger_ui',
        ],
        'drupalSettings' => [
          'openapi' => [
            'json_url' => $json_url->toString(),
          ],
        ],
      ],
    ];
    return $build;
  }

  /**
   * Creates documentations page for non-entity resources.
   *
   * @return array
   *   Render array for documentations page.
   */
  public function openApiResources() {
    $json_url = Url::fromRoute($this->getJsonGeneratorRoute());
    $build = $this->swaggerUi($json_url);
    return $build;
  }

  /**
   * Returns route for generating JSON.
   *
   * @return string
   *   The route name.
   */
  abstract protected function getJsonGeneratorRoute();

}
