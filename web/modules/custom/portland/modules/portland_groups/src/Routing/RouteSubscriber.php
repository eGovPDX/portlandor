<?php

namespace Drupal\portland_groups\Routing;

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
    // override routes from the Group gnode and groupmedia modules; we want to customize the add page.
    if ($route = $collection->get('entity.group_content.group_node_add_page')) {
        $route->setDefault('_controller', '\Drupal\portland_groups\Controller\PortlandGroupContentController::addPage');
    }
    if ($route = $collection->get('entity.group_content.group_media_add_page')) {
        $route->setDefault('_controller', '\Drupal\portland_groups\Controller\PortlandGroupMediaController::addPage');
    }

  }

}
