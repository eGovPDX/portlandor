<?php

namespace Drupal\views_bulk_operations\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Session\AccountInterface;
use Drupal\views_bulk_operations\Service\ViewsbulkOperationsViewDataInterface;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager;
use Drupal\user\Entity\User;
use Drupal\views\Views;
use Drupal\views_bulk_operations\ViewsBulkOperationsBatch;

/**
 * Defines Drush commands for the module.
 */
class ViewsBulkOperationsCommands extends DrushCommands {

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Object that gets the current view data.
   *
   * @var \Drupal\views_bulk_operations\ViewsbulkOperationsViewDataInterface
   */
  protected $viewData;

  /**
   * Views Bulk Operations action manager.
   *
   * @var \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager
   */
  protected $actionManager;

  /**
   * ViewsBulkOperationsCommands object constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user object.
   * @param \Drupal\views_bulk_operations\ViewsbulkOperationsViewDataInterface $viewData
   *   VBO View data service.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager $actionManager
   *   VBO Action manager service.
   */
  public function __construct(
    AccountInterface $currentUser,
    ViewsbulkOperationsViewDataInterface $viewData,
    ViewsBulkOperationsActionManager $actionManager
  ) {
    $this->currentUser = $currentUser;
    $this->viewData = $viewData;
    $this->actionManager = $actionManager;
  }

  /**
   * Execute an action on all results of the specified view.
   *
   * Use the --verbose parameter to see progress messages.
   *
   * @param string $view_id
   *   The ID of the view to use.
   * @param string $action_id
   *   The ID of the action to execute.
   * @param array $options
   *   (optional) An array of options.
   *
   * @return string
   *   The summary message.
   *
   * @command views-bulk-operations:execute
   *
   * @option display-id
   *   ID of the display to use.
   * @option args
   *   View arguments (slash is a delimeter).
   * @option exposed
   *   Exposed filters (query string format).
   * @option batch-size
   *   Processing batch size.
   * @option configuration
   *   Action configuration (query string format).
   * @option user-id
   *   The ID of the user account used for performing the operation.
   *
   * @usage drush views-bulk-operations:execute some_view some_action
   *   Execute some action on some view.
   * @usage drush vbo-execute some_view some_action --args=arg1/arg2 --batch-size=50
   *   Execute some action on some view with arg1 and arg2 as
   *   the view arguments and 50 entities processed per batch.
   * @usage drush vbo-exec some_view some_action --configuration=&quot;key1=value1&amp;key2=value2&quot;
   *   Execute some action on some view with the specified action configuration.
   *
   * @aliases vbo-execute, vbo-exec
   */
  public function vboExecute(
    $view_id,
    $action_id,
    array $options = [
      'display-id' => 'default',
      'args' => '',
      'exposed' => '',
      'batch-size' => 100,
      'configuration' => '',
      'user-id' => 1,
    ]
  ) {

    if (empty($view_id) || empty($action_id)) {
      throw new \Exception($this->t('You must specify the view ID and the action ID parameters.'));
    }

    $this->timer($options['verbose']);

    // Prepare options.
    if ($options['args']) {
      $options['args'] = explode('/', $options['args']);
    }
    else {
      $options['args'] = [];
    }

    // Decode query string format options.
    foreach (['configuration', 'exposed'] as $name) {
      if (!empty($options[$name]) && !is_array($options[$name])) {
        parse_str($options[$name], $options[$name]);
      }
      else {
        $options[$name] = [];
      }
    }

    $vbo_data = [
      'list' => [],
      'view_id' => $view_id,
      'display_id' => $options['display-id'],
      'action_id' => $action_id,
      'preconfiguration' => $options['configuration'],
      'batch' => TRUE,
      'arguments' => $options['args'],
      'exposed_input' => $options['exposed'],
      'batch_size' => $options['batch-size'],
      'relationship_id' => 'none',
    ];

    // Login as superuser, as drush 9 doesn't support the
    // --user parameter.
    $account = User::load($options['user-id']);
    $this->currentUser->setAccount($account);

    // Initialize the view to check if parameters are correct.
    if (!$view = Views::getView($vbo_data['view_id'])) {
      throw new \Exception($this->t('Incorrect view ID provided.'));
    }
    if (!$view->setDisplay($vbo_data['display_id'])) {
      throw new \Exception($this->t('Incorrect view display ID provided.'));
    }
    if (!empty($vbo_data['arguments'])) {
      $view->setArguments($vbo_data['arguments']);
    }
    if (!empty($vbo_data['exposed_input'])) {
      $view->setExposedInput($vbo_data['exposed_input']);
    }

    // We need total rows count for proper progress message display.
    $view->get_total_rows = TRUE;
    $view->execute();

    // Get relationship ID if VBO field exists.
    $vbo_data['relationship_id'] = 'none';
    foreach ($view->field as $field) {
      if ($field->options['id'] === 'views_bulk_operations_bulk_form') {
        $vbo_data['relationship_id'] = $field->options['relationship'];
      }
    }

    // Get total rows count.
    $this->viewData->init($view, $view->getDisplay(), $vbo_data['relationship_id']);
    $vbo_data['total_results'] = $this->viewData->getTotalResults();

    // Get action definition and check if action ID is correct.
    $action_definition = $this->actionManager->getDefinition($action_id);
    $vbo_data['action_label'] = (string) $action_definition['label'];

    $this->timer($options['verbose'], 'init');

    // Populate entity list.
    $context = [];
    do {
      $context['finished'] = 1;
      $context['message'] = '';
      ViewsBulkOperationsBatch::getList($vbo_data, $context);
      if (!empty($context['message'])) {
        $this->logger->info($context['message']);
      }
    } while ($context['finished'] < 1);
    $vbo_data = $context['results'];

    $this->timer($options['verbose'], 'list');

    // Execute the selected action.
    $context = [];
    do {
      $context['finished'] = 1;
      $context['message'] = '';
      ViewsBulkOperationsBatch::operation($vbo_data, $context);
      if (!empty($context['message'])) {
        $this->logger->info($context['message']);
      }
    } while ($context['finished'] < 1);

    // Output a summary message.
    $operations = array_count_values($context['results']['operations']);
    $details = [];
    foreach ($operations as $op => $count) {
      $details[] = $op . ' (' . $count . ')';
    }

    // Display debug information.
    if ($options['verbose']) {
      $this->timer($options['verbose'], 'execute');
      $this->logger->info($this->t('Initialization time: @time ms.', ['@time' => $this->timer($options['verbose'], 'init')]));
      $this->logger->info($this->t('Entity list generation time: @time ms.', ['@time' => $this->timer($options['verbose'], 'list')]));
      $this->logger->info($this->t('Execution time: @time ms.', ['@time' => $this->timer($options['verbose'], 'execute')]));
    }

    return $this->t('Action processing results: @results.', ['@results' => implode(', ', $details)]);
  }

  /**
   * Helper function to set / get timer.
   *
   * @param bool $debug
   *   Should the function do anything at all?
   * @param string $id
   *   ID of a specific timer span.
   *
   * @return mixed
   *   NULL or value of a specific timer if set.
   */
  protected function timer($debug = TRUE, $id = NULL) {
    if (!$debug) {
      return;
    }

    static $timers = [];

    if (!isset($id)) {
      $timers['start'] = microtime(TRUE);
    }
    else {
      if (isset($timers[$id])) {
        end($timers);
        do {
          if (key($timers) === $id) {
            return round((current($timers) - prev($timers)) * 1000, 3);
          }
          else {
            $result = prev($timers);
          }
        } while ($result);
      }
      else {
        $timers[$id] = microtime(TRUE);
      }
    }
  }

  /**
   * Translates a string using the dt function.
   *
   * @param string $message
   *   The message to translate.
   * @param array $arguments
   *   (optional) The translation arguments.
   *
   * @return string
   *   The translated message.
   */
  protected function t($message, array $arguments = []) {
    return dt($message, $arguments);
  }

}
