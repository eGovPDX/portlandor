<?php

namespace Drupal\openapi_redoc\Routing;

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
   * Provides dynamic routes.
   */
  public function routes() {
    $collection = new RouteCollection();
    foreach (['rest', 'jsonapi'] as $api_module) {
      if ($this->moduleHandler->moduleExists($api_module)) {
        $route_name = "openapi_redoc.$api_module";
        $route = (new Route("/admin/config/services/openapi/redoc/$api_module"))
          ->setDefault(RouteObjectInterface::CONTROLLER_NAME, '\Drupal\openapi_redoc\Controller\DocController::generateDocs')
          ->setDefault('api_module', $api_module)
          ->setDefault('_title_callback', '\Drupal\openapi_redoc\Controller\DocController::getTitle')
          ->setMethods(['GET'])
          ->setRequirements([
            '_permission' => 'access openapi api docs',
          ]);
        $collection->add($route_name, $route);
      }
    }
    return $collection;
  }

}
