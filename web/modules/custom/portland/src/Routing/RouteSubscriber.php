<?php

namespace Drupal\portland\Routing;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\search_api_page\Routing\SearchApiPageRoutes;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {
  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  public function __construct(
    LoggerChannelFactoryInterface $logger_factory,
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager
  ) {
    $this->loggerFactory = $logger_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // override route entity.group_content.group_node_add_page from Group module.
    // we want this to instead load a page that allows user to select a group content type,
    // but in our case, we want the content type descriptions to be displayed.
    if ($route = $collection->get('entity.group_content.group_node_add_page')) {
        $route->setDefault('_controller', '\Drupal\portland\Controller\PortlandController::addPage');
    }

    if(!isset($_ENV['PANTHEON_ENVIRONMENT'])) {
      $variables = [
        '@message' => '$_ENV["PANTHEON_ENVIRONMENT"] not set.',
      ];
      $this->loggerFactory->get('portland')
        ->warning('@message', $variables);
    }

    // custom overrides on test and live environments
    if(
      isset($_ENV['PANTHEON_ENVIRONMENT']) &&
      in_array($_ENV['PANTHEON_ENVIRONMENT'], ['powr-507', 'dev', 'test', 'live'])
    ) {
      // only log in with an OpenID provider
      if ($route = $collection->get('user.login')) {
        $route->setDefault('_form', 'Drupal\openid_connect\Form\LoginForm');
      }
      // don't accept POSTs to a login route
      if ($route = $collection->get('user.login.http')) {
        $route->setRequirement('_access', 'FALSE');
      }
      // don't allow password resets via Drupal
      if($route = $collection->get('user.pass')) {
        $route->setRequirement('_access', 'FALSE');
      }
      // don't accept POSTs to a password reset form
      if($route = $collection->get('user.pass.http')) {
        $route->setRequirement('_access', 'FALSE');
      }
    }

    // Override the SearchApiPageRoutes dynamic routes with
    // our static search routes
    $searchApiPageRoutes = new SearchApiPageRoutes(
      $this->entityTypeManager,
      $this->languageManager
    );

    $searchApiRoutes = $searchApiPageRoutes->routes();

    foreach (array_keys($searchApiRoutes) as $key) {
      if($route = $collection->get($key)) {
        $route->setDefault('_controller', 'Drupal\portland\Controller\PortlandSearchApiPageController::page');
      }
    }
  }

}
