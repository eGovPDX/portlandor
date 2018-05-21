<?php

namespace Drupal\ds\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for Display Suite UI routes.
 */
class DsController extends ControllerBase {

  /**
   * Lists all bundles per entity type.
   *
   * @return array
   *   The Views fields report page.
   */
  public function listDisplays() {
    $build = [];

    // All entities.
    $entity_info = $this->entityTypeManager()->getDefinitions();

    // Move node to the top.
    if (isset($entity_info['node'])) {
      $node_entity = $entity_info['node'];
      unset($entity_info['node']);
      $entity_info = array_merge(['node' => $node_entity], $entity_info);
    }

    $field_ui_enabled = $this->moduleHandler()->moduleExists('field_ui');
    if (!$field_ui_enabled) {
      $build['no_field_ui'] = [
        '#markup' => '<p>' . $this->t('You need to enable Field UI to manage the displays of entities.') . '</p>',
        '#weight' => -10,
      ];
    }

    if (isset($entity_info['comment'])) {
      $comment_entity = $entity_info['comment'];
      unset($entity_info['comment']);
      $entity_info['comment'] = $comment_entity;
    }

    foreach ($entity_info as $entity_type => $info) {
      $base_table = $info->getBaseTable();
      if ($info->get('field_ui_base_route') && !empty($base_table)) {
        $rows = [];
        $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type);
        foreach ($bundles as $bundle_type => $bundle) {
          $row = [];
          $operations = [];
          $row[] = [
            'data' => [
              '#plain_text' => $bundle['label'],
            ],
          ];

          if ($field_ui_enabled) {
            // Get the manage display URI.
            $route = FieldUI::getOverviewRouteInfo($entity_type, $bundle_type);
            if ($route) {
              $operations['manage_display'] = [
                'title' => $this->t('Manage display'),
                'url' => new Url("entity.entity_view_display.$entity_type.default", $route->getRouteParameters()),
              ];
            }
          }

          // Add operation links.
          if (!empty($operations)) {
            $row[] = [
              'data' => [
                '#type' => 'operations',
                '#subtype' => 'ds',
                '#links' => $operations,
              ],
            ];
          }
          else {
            $row[] = ['data' => ''];
          }

          $rows[] = $row;
        }

        if (!empty($rows)) {
          $header = [
            ['data' => $info->getLabel()],
            [
              'data' => $field_ui_enabled ? $this->t('operations') : '',
              'class' => 'ds-display-list-options',
            ],
          ];
          $build['list_' . $entity_type] = [
            '#theme' => 'table',
            '#header' => $header,
            '#rows' => $rows,
          ];
        }
      }
    }

    $build['#attached']['library'][] = 'ds/admin';

    return $build;
  }

  /**
   * Adds a contextual tab to entities.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route information.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response pointing to the corresponding display.
   */
  public function contextualTab(RouteMatchInterface $route_match) {
    $parameter_name = $route_match->getRouteObject()->getOption('_ds_entity_type_id');
    $entity = $route_match->getParameter($parameter_name);
    $entity_type_id = $entity->getEntityTypeId();

    $destination = $entity->toUrl();

    if (!empty($entity->ds_switch->value)) {
      $view_mode = $entity->ds_switch->value;
    }
    else {
      $view_mode = 'full';
    }

    // Get the manage display URI.
    $route = FieldUI::getOverviewRouteInfo($entity_type_id, $entity->bundle());

    /* @var $entity_display EntityDisplayBase */
    $entity_display = EntityViewDisplay::load($entity_type_id . '.' . $entity->bundle() . '.' . $view_mode);

    $route_parameters = $route->getRouteParameters();
    if ($entity_display && $entity_display->status() && $entity_display->getThirdPartySetting('ds', 'layout')) {
      $route_parameters['view_mode_name'] = $view_mode;
      $admin_route_name = "entity.entity_view_display.$entity_type_id.view_mode";
    }
    else {
      $admin_route_name = "entity.entity_view_display.$entity_type_id.default";
    }
    $route->setOption('query', ['destination' => $destination->toString()]);

    $url = new Url($admin_route_name, $route_parameters, $route->getOptions());

    return new RedirectResponse($url->setAbsolute(TRUE)->toString());
  }

}
