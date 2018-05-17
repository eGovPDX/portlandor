<?php

namespace Drupal\views_bulk_operations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\views_bulk_operations\Form\ViewsBulkOperationsFormTrait;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Ajax\AjaxResponse;

/**
 * Defines VBO controller class.
 */
class ViewsBulkOperationsController extends ControllerBase implements ContainerInjectionInterface {

  use ViewsBulkOperationsFormTrait;

  /**
   * User private temporary storage factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Views Bulk Operations action processor.
   *
   * @var \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface
   */
  protected $actionProcessor;

  /**
   * Constructs a new controller object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $tempStoreFactory
   *   User private temporary storage factory.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface $actionProcessor
   *   Views Bulk Operations action processor.
   */
  public function __construct(
    PrivateTempStoreFactory $tempStoreFactory,
    ViewsBulkOperationsActionProcessorInterface $actionProcessor
  ) {
    $this->tempStoreFactory = $tempStoreFactory;
    $this->actionProcessor = $actionProcessor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('views_bulk_operations.processor')
    );
  }

  /**
   * The actual page callback.
   *
   * @param string $view_id
   *   The current view ID.
   * @param string $display_id
   *   The display ID of the current view.
   */
  public function execute($view_id, $display_id) {
    $view_data = $this->getTempstoreData($view_id, $display_id);
    if (empty($view_data)) {
      throw new NotFoundHttpException();
    }
    $this->deleteTempstoreData();

    $this->actionProcessor->executeProcessing($view_data);
    return batch_process($view_data['redirect_url']);
  }

  /**
   * AJAX callback to update selection (multipage).
   *
   * @param string $view_id
   *   The current view ID.
   * @param string $display_id
   *   The display ID of the current view.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function updateSelection($view_id, $display_id, Request $request) {
    $view_data = $this->getTempstoreData($view_id, $display_id);
    if (empty($view_data)) {
      throw new NotFoundHttpException();
    }

    $list = $request->request->get('list');

    $op = $request->request->get('op', 'add');
    $change = 0;

    if ($op === 'add') {
      foreach ($list as $bulkFormKey => $label) {
        if (!isset($view_data['list'][$bulkFormKey])) {
          $view_data['list'][$bulkFormKey] = $this->getListItem($bulkFormKey, $label);
          $change++;
        }
      }
    }
    elseif ($op === 'remove') {
      foreach ($list as $bulkFormKey => $label) {
        if (isset($view_data['list'][$bulkFormKey])) {
          unset($view_data['list'][$bulkFormKey]);
          $change--;
        }
      }
    }
    $this->setTempstoreData($view_data);

    $response = new AjaxResponse();
    $response->setData(['change' => $change]);
    return $response;
  }

}
