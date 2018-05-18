<?php

namespace Drupal\views_bulk_operations;

use Symfony\Component\EventDispatcher\Event;
use Drupal\views\ViewExecutable;

/**
 * Defines Views Bulk Operations event type.
 */
class ViewsBulkOperationsEvent extends Event {

  const NAME = 'views_bulk_operations.view_data';

  /**
   * The provider of the current view.
   *
   * @var string
   */
  protected $provider;

  /**
   * The views data of the current view.
   *
   * @var array
   */
  protected $viewData;

  /**
   * The current view object.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * IDs of entity types returned by the view.
   *
   * @var array
   */
  protected $entityTypeIds;

  /**
   * Row entity getter information.
   *
   * @var array
   */
  protected $entityGetter;

  /**
   * Object constructor.
   *
   * @param string $provider
   *   The provider of the current view.
   * @param array $viewData
   *   The views data of the current view.
   * @param \Drupal\views\ViewExecutable $view
   *   The current view.
   */
  public function __construct($provider, array $viewData, ViewExecutable $view) {
    $this->provider = $provider;
    $this->viewData = $viewData;
    $this->view = $view;
  }

  /**
   * Get view provider.
   *
   * @return string
   *   The view provider
   */
  public function getProvider() {
    return $this->provider;
  }

  /**
   * Get view data.
   *
   * @return string
   *   The current view data
   */
  public function getViewData() {
    return $this->viewData;
  }

  /**
   * Get current view.
   *
   * @return \Drupal\views\ViewExecutable
   *   The current view object
   */
  public function getView() {
    return $this->view;
  }

  /**
   * Get entity type IDs displayed by the current view.
   *
   * @return array
   *   Entity type IDs.
   */
  public function getEntityTypeIds() {
    return $this->entityTypeIds;
  }

  /**
   * Get entity getter callable.
   *
   * @return array
   *   Entity getter information.
   */
  public function getEntityGetter() {
    return $this->entityGetter;
  }

  /**
   * Set entity type IDs.
   *
   * @param array $entityTypeIds
   *   Entity type IDs.
   */
  public function setEntityTypeIds(array $entityTypeIds) {
    $this->entityTypeIds = $entityTypeIds;
  }

  /**
   * Set entity getter callable.
   *
   * @param array $entityGetter
   *   Entity getter information.
   */
  public function setEntityGetter(array $entityGetter) {
    if (!isset($entityGetter['callable'])) {
      throw new \Exception('Views Bulk Operations entity getter callable is not defined.');
    }
    $this->entityGetter = $entityGetter;
  }

}
