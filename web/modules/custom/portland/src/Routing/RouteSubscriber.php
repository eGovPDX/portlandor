<?php

namespace Drupal\portland\Routing;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {
  /**
   * The content translation manager.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a ContentTranslationRouteSubscriber object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The content translation manager.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->loggerFactory = $logger_factory;
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

        if(isset($_ENV['PANTHEON_ENVIRONMENT'])) {
          $variables = [
            '@label' => '$_ENV["PANTHEON_ENVIRONMENT"]',
            '@value' => $_ENV['PANTHEON_ENVIRONMENT'],
          ];
          $this->loggerFactory->get('portland')
            ->warning('@label = @value', $variables);
        }

        // custom overrides on test and live environments
        if(
          isset($_ENV['PANTHEON_ENVIRONMENT']) &&
          in_array($_ENV['PANTHEON_ENVIRONMENT'], ['powr-279', 'dev', 'test', 'live'])
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
    }

}
