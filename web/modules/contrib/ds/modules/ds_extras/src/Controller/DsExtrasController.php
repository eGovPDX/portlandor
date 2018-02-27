<?php

namespace Drupal\ds_extras\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Display Suite Extra routes.
 */
class DsExtrasController extends ControllerBase {

  /**
   * Returns an node through JSON.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The global request object.
   * @param string $entityType
   *   The type of the requested entity.
   * @param string $entityId
   *   The id of the requested entity.
   * @param string $viewMode
   *   The view mode you wish to render for the requested entity.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An ajax response with the new view mode.
   */
  public function switchViewMode(Request $request, $entityType, $entityId, $viewMode) {
    $response = new AjaxResponse();
    $entity = $this->entityTypeManager()
      ->getStorage($entityType)
      ->load($entityId);

    if ($entity->access('view')) {
      $element = $this->entityTypeManager()
        ->getViewBuilder($entityType)
        ->view($entity, $viewMode);
      $content = \Drupal::service('renderer')->render($element, FALSE);

      $response->addCommand(new ReplaceCommand('.' . $request->get('selector'), $content));
    }

    return $response;
  }

  /**
   * Displays a node revision.
   *
   * @param int $node_revision
   *   The node revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($node_revision) {
    /* @var \Drupal\node\NodeInterface $node */
    $node = $this->entityTypeManager()
      ->getStorage('node')
      ->loadRevision($node_revision);

    // Determine view mode.
    $view_mode = \Drupal::config('ds_extras.settings')
      ->get('override_node_revision_view_mode');

    drupal_static('ds_view_mode', $view_mode);

    $page = node_view($node, $view_mode);
    unset($page['nodes'][$node->id()]['#cache']);

    return $page;
  }

  /**
   * Checks access for the switch view mode route.
   */
  public function accessSwitchViewMode() {
    return $this->config('ds_extras.settings')
      ->get('switch_field') ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
