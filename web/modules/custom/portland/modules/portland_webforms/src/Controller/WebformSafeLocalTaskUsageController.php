<?php

namespace Drupal\portland_webforms\Controller;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityInterface;

/**
 * Safe local task controller for Entity Usage that handles config entities.
 */
final class WebformSafeLocalTaskUsageController extends WebformSafeListUsageController {

  /**
   * Checks access based on whether the user can view the current entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A RouteMatch object.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccessLocalTask(RouteMatchInterface $route_match): AccessResultInterface {
    $entity = $this->getEntityFromRouteMatch($route_match);
    return parent::checkAccess($entity->getEntityTypeId(), $entity->id());
  }

  /**
   * Title page callback.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A RouteMatch object.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title to be used on this page.
   */
  public function getTitleLocalTask(RouteMatchInterface $route_match): TranslatableMarkup {
    $entity = $this->getEntityFromRouteMatch($route_match);
    return parent::getTitle($entity->getEntityTypeId(), $entity->id());
  }

  /**
   * Lists the usage of a given entity.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   A RouteMatch object.
   *
   * @return mixed[]
   *   The page build to be rendered.
   */
  public function listUsageLocalTask(RouteMatchInterface $route_match): array {
    $entity = $this->getEntityFromRouteMatch($route_match);
    return parent::listUsagePage($entity->getEntityTypeId(), $entity->id());
  }

  /**
   * Retrieves entity from route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object as determined from the passed-in route match.
   */
  protected function getEntityFromRouteMatch(RouteMatchInterface $route_match): ?EntityInterface {
    $parameter_name = $route_match->getRouteObject()->getOption('_entity_usage_entity_type_id');
    return $route_match->getParameter($parameter_name);
  }

}
