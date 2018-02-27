<?php

namespace Drupal\openapi\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes.
 *
 * @internal
 */
class Routes implements ContainerInjectionInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Routes constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * Creates the routs for OpenAPI specification controllers.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The route collection.
   */
  public function routes() {
    $collection = new RouteCollection();
    /** @var \Symfony\Component\Routing\Route[] $specification_routes */
    $specification_routes = [];
    if ($this->moduleHandler->moduleExists('rest')) {
      $specification_routes['openapi.rest'] = (new Route('/openapi/rest'))
        ->setDefault(RouteObjectInterface::CONTROLLER_NAME, '\Drupal\openapi\Controller\RestSpecificationController::getSpecification');
    }
    if ($this->moduleHandler->moduleExists('jsonapi')) {
      $specification_routes['openapi.jsonapi'] = (new Route('/openapi/jsonapi'))
        ->setDefault(RouteObjectInterface::CONTROLLER_NAME, '\Drupal\openapi\Controller\JsonApiSpecificationController::getSpecification');
    }
    if ($specification_routes) {
      foreach ($specification_routes as $route_name => $route) {
        $route->setMethods(['GET'])
          ->setRequirements([
            '_permission' => 'access openapi api docs',
            '_format' => 'json',
          ]);
        $collection->add($route_name, $route);
      }
    }
    // Add downloads route even if no other routes. To inform user.
    $downloads_route = new Route('/admin/config/services/openapi-downloads');
    $downloads_route->setDefault(RouteObjectInterface::CONTROLLER_NAME, '\Drupal\openapi\Controller\OpenApiDownloadController::downloadsList')
      ->setMethods(['GET'])
      ->setRequirements([
        '_permission' => 'access openapi api docs',
      ]);
    $collection->add('openapi.downloads', $downloads_route);
    return $collection;
  }

}
