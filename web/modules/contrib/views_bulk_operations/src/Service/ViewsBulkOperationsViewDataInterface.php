<?php

namespace Drupal\views_bulk_operations\Service;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;

/**
 * Defines view data service for Views Bulk Operations.
 */
interface ViewsBulkOperationsViewDataInterface {

  /**
   * Initialize additional variables.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object.
   * @param \Drupal\views\Plugin\views\display\DisplayPluginBase $display
   *   The current display plugin.
   * @param string $relationship
   *   Relationship ID.
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, $relationship);

  /**
   * Get entity type IDs.
   *
   * @return array
   *   Array of entity type IDs.
   */
  public function getEntityTypeIds();

  /**
   * Get view provider.
   *
   * @return string
   *   View provider ID.
   */
  public function getViewProvider();

  /**
   * Get base field for the current view.
   *
   * @return sting
   *   The base field name.
   */
  public function getViewBaseField();

  /**
   * Get entity from views row.
   *
   * @param \Drupal\views\ResultRow $row
   *   Views row object.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   An entity object.
   */
  public function getEntity(ResultRow $row);

  /**
   * Get the total count of results on all pages.
   *
   * @return int
   *   The total number of results this view displays.
   */
  public function getTotalResults();

  /**
   * The default entity getter function.
   *
   * Must work well with standard Drupal core entity views.
   *
   * @param \Drupal\views\ResultRow $row
   *   Views result row.
   * @param string $relationship_id
   *   Id of the view relationship.
   * @param \Drupal\views\ViewExecutable $view
   *   The current view object.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   The translated entity.
   */
  public function getEntityDefault(ResultRow $row, $relationship_id, ViewExecutable $view);

}
