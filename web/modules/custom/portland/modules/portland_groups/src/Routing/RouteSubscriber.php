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
    // override routes from the Group gnode and groupmedia modules to set our own controller::method
    if ($route = $collection->get('entity.group_content.group_node_create_page')) {
      $route->setDefault('_controller', '\Drupal\portland_groups\Controller\PortlandGroupRelationshipController::addPage');
    }
    if ($route = $collection->get('entity.group_content.group_node_add_page')) {
      $route->setDefault('_controller', '\Drupal\portland_groups\Controller\PortlandGroupRelationshipController::addPage');
    }
    if ($route = $collection->get('entity.group_content.group_media_create_page')) {
      $route->setDefault('_controller', '\Drupal\portland_groups\Controller\PortlandGroupMediaController::addPage');
      $route->setDefault('_title', 'Create media');
    }
    if ($route = $collection->get('entity.group_content.group_media_add_page')) {
      $route->setDefault('_controller', '\Drupal\portland_groups\Controller\PortlandGroupMediaController::addPage');
      $route->setDefault('_title', 'Relate media');
    }

    // override group create content route to set our own title callback so we can customize it
    if ($route = $collection->get('entity.group_content.create_form')) {
      $route->setDefault('_title_callback', '\Drupal\portland_groups\Controller\PortlandGroupRelationshipController::createFormTitle');
    }

    // make group revisions tab and revert page use the admin theme
    if ($route = $collection->get('entity.group.version_history')) {
      $route->setOption('_admin_route', true);
    }
    if ($route = $collection->get('entity.group.revision_revert_form')) {
      $route->setOption('_admin_route', true);
    }
  }
}
