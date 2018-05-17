<?php

namespace Drupal\views_bulk_operations\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsViewDataInterface;
use Drupal\views_bulk_operations\ViewsBulkOperationsEvent;

/**
 * Defines module event subscriber class.
 *
 * Allows getting data of core entity views.
 */
class ViewsBulkOperationsEventSubscriber implements EventSubscriberInterface {

  // Subscribe to the VBO event with high priority
  // to prepopulate the event data.
  const PRIORITY = 999;

  /**
   * Object that gets the current view data.
   *
   * @var \Drupal\views_bulk_operations\ViewsBulkOperationsViewDataInterface
   */
  protected $viewData;

  /**
   * Object constructor.
   *
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsViewDataInterface $viewData
   *   The VBO View Data provider service.
   */
  public function __construct(ViewsBulkOperationsViewDataInterface $viewData) {
    $this->viewData = $viewData;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ViewsBulkOperationsEvent::NAME][] = ['provideViewData', self::PRIORITY];
    return $events;
  }

  /**
   * Respond to view data request event.
   *
   * @var \Drupal\views_bulk_operations\ViewsBulkOperationsEvent $event
   *   The event to respond to.
   */
  public function provideViewData(ViewsBulkOperationsEvent $event) {
    $view_data = $event->getViewData();
    if (!empty($view_data['table']['entity type'])) {
      $event->setEntityTypeIds([$view_data['table']['entity type']]);
      $event->setEntityGetter([
        'callable' => [$this->viewData, 'getEntityDefault'],
      ]);
    }
  }

}
