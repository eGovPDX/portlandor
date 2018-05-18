<?php

namespace Drupal\views_bulk_operations\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Access\AccessResult;
use Drupal\views\Views;

/**
 * Defines module access rules.
 */
class ViewsBulkOperationsAccess implements AccessInterface {

  /**
   * Temporary user storage object.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Object constructor.
   */
  public function __construct(PrivateTempStoreFactory $tempStoreFactory) {
    $this->tempStoreFactory = $tempStoreFactory;
  }

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\Core\Routing\RouteMatch $routeMatch
   *   The matched route.
   */
  public function access(AccountInterface $account, RouteMatch $routeMatch) {
    $parameters = $routeMatch->getParameters()->all();

    if ($view = Views::getView($parameters['view_id'])) {
      if ($view->access($parameters['display_id'], $account)) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::forbidden();
  }

}
