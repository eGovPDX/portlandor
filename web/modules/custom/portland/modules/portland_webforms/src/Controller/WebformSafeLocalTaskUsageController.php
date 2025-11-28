<?php

namespace Drupal\portland_webforms\Controller;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityInterface;

/**
 * Local task adapter for the safe Entity Usage list controller.
 *
 * This controller mirrors the upstream Entity Usage local task behaviour but
 * delegates to WebformSafeListUsageController. It exists as a spare:
 * currently no routes or local tasks point at these callbacks, so the core
 * Entity Usage tab continues to use contrib's controllers.
 *
 * If we ever need to swap in our safe listing for the standard Usage tab,
 * or expose a separate "Webform usage" tab, we can update routing and
 * local task definitions to use these methods instead.
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
    if (!$entity) {
      return \Drupal\Core\Access\AccessResult::forbidden();
    }
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
    if (!$entity) {
      // Fallback title if entity cannot be resolved.
      return $this->t('Usage');
    }
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
    if (!$entity) {
      return [
        '#type' => 'markup',
        '#markup' => $this->t('Unable to determine the target entity for usage.'),
      ];
    }
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
    $route = $route_match->getRouteObject();
    if (!$route) {
      return NULL;
    }

    $parameter_name = $route->getOption('_entity_usage_entity_type_id');
    if (!is_string($parameter_name)) {
      return NULL;
    }

    $entity = $route_match->getParameter($parameter_name);
    return $entity instanceof EntityInterface ? $entity : NULL;
  }

}
