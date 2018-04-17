<?php

namespace Drupal\views_bulk_operations\Plugin\views\field;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\UncacheableFieldHandlerTrait;
use Drupal\views\Plugin\views\style\Table;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views_bulk_operations\Service\ViewsbulkOperationsViewDataInterface;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;
use Drupal\views_bulk_operations\Form\ViewsBulkOperationsFormTrait;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Url;

/**
 * Defines the Views Bulk Operations field plugin.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("views_bulk_operations_bulk_form")
 */
class ViewsBulkOperationsBulkForm extends FieldPluginBase implements CacheableDependencyInterface, ContainerFactoryPluginInterface {

  use RedirectDestinationTrait;
  use UncacheableFieldHandlerTrait;
  use ViewsBulkOperationsFormTrait;

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
   * Views Bulk Operations action processor.
   *
   * @var \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface
   */
  protected $actionProcessor;

  /**
   * User private temporary storage factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * An array of actions that can be executed.
   *
   * @var array
   */
  protected $actions = [];

  /**
   * An array of bulk form options.
   *
   * @var array
   */
  protected $bulkOptions;

  /**
   * Tempstore data.
   *
   * This gets passed to the next requests if needed
   * or used in the views form submit handler directly.
   *
   * @var array
   */
  protected $tempStoreData = [];

  /**
   * Constructs a new BulkForm object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\views_bulk_operations\Service\ViewsbulkOperationsViewDataInterface $viewData
   *   The VBO View Data provider service.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager $actionManager
   *   Extended action manager object.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface $actionProcessor
   *   Views Bulk Operations action processor.
   * @param \Drupal\user\PrivateTempStoreFactory $tempStoreFactory
   *   User private temporary storage factory.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ViewsbulkOperationsViewDataInterface $viewData,
    ViewsBulkOperationsActionManager $actionManager,
    ViewsBulkOperationsActionProcessorInterface $actionProcessor,
    PrivateTempStoreFactory $tempStoreFactory,
    AccountInterface $currentUser,
    RequestStack $requestStack
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->viewData = $viewData;
    $this->actionManager = $actionManager;
    $this->actionProcessor = $actionProcessor;
    $this->tempStoreFactory = $tempStoreFactory;
    $this->currentUser = $currentUser;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('views_bulk_operations.data'),
      $container->get('plugin.manager.views_bulk_operations_action'),
      $container->get('views_bulk_operations.processor'),
      $container->get('user.private_tempstore'),
      $container->get('current_user'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // Don't initialize if view has been built from VBO action processor.
    if (!empty($this->view->views_bulk_operations_processor_built)) {
      return;
    }

    // Set this property to always have the total rows information.
    $this->view->get_total_rows = TRUE;

    // Initialize VBO View Data object.
    $this->viewData->init($view, $display, $this->options['relationship']);

    // Fetch actions.
    $this->actions = [];
    $entity_types = $this->viewData->getEntityTypeIds();

    // Get actions only if there are any entity types set for the view.
    if (!empty($entity_types)) {
      foreach ($this->actionManager->getDefinitions() as $id => $definition) {
        if (empty($definition['type']) || in_array($definition['type'], $entity_types, TRUE)) {
          $this->actions[$id] = $definition;
        }
      }
    }

    // Force form_step setting to TRUE due to #2879310.
    $this->options['form_step'] = TRUE;
  }

  /**
   * Update tempstore data.
   *
   * This function must be called a bit later, when the view
   * query has been built. Also, no point doing this on the view
   * admin page.
   */
  protected function updateTempstoreData() {
    // Initialize tempstore object and get data if available.
    $this->tempStoreData = $this->getTempstoreData($this->view->id(), $this->view->current_display);

    // Parameters subject to change (either by an admin or user action).
    $variable = [
      'batch' => $this->options['batch'],
      'batch_size' => $this->options['batch'] ? $this->options['batch_size'] : 0,
      'total_results' => $this->viewData->getTotalResults(),
      'arguments' => $this->view->args,
      'redirect_url' => Url::createFromRequest($this->requestStack->getCurrentRequest()),
      'exposed_input' => $this->view->getExposedInput(),
    ];

    // Create tempstore data object if it doesn't exist.
    if (!is_array($this->tempStoreData)) {
      $this->tempStoreData = [];

      // Add constant parameters.
      $this->tempStoreData += [
        'view_id' => $this->view->id(),
        'display_id' => $this->view->current_display,
        'list' => [],
      ];

      // Add variable parameters.
      $this->tempStoreData += $variable;

      $this->setTempstoreData($this->tempStoreData);
    }

    // Update some of the tempstore data parameters if required.
    else {
      $update = FALSE;

      // Delete list if view arguments and optionally exposed filters changed.
      // NOTE: this should be subject to a discussion, maybe tempstore
      // should be arguments - specific?
      $clear_triggers = ['arguments'];
      if ($this->options['clear_on_exposed']) {
        $clear_triggers[] = 'exposed_input';
      }

      foreach ($clear_triggers as $trigger) {
        if ($variable[$trigger] !== $this->tempStoreData[$trigger]) {
          $this->tempStoreData[$trigger] = $variable[$trigger];
          $this->tempStoreData['list'] = [];
        }
        unset($variable[$trigger]);
        $update = TRUE;
      }

      foreach ($variable as $param => $value) {
        if (!isset($this->tempStoreData[$param]) || $this->tempStoreData[$param] != $value) {
          $update = TRUE;
          $this->tempStoreData[$param] = $value;
        }
      }

      if ($update) {
        $this->setTempstoreData($this->tempStoreData);
      }
    }

  }

  /**
   * Gets the current user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  protected function currentUser() {
    return $this->currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // @todo Consider making the bulk operation form cacheable. See
    //   https://www.drupal.org/node/2503009.
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(ResultRow $row) {
    return $this->viewData->getEntity($row);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['batch'] = ['default' => TRUE];
    $options['batch_size'] = ['default' => 10];
    $options['form_step'] = ['default' => TRUE];
    $options['buttons'] = ['default' => FALSE];
    $options['clear_on_exposed'] = ['default' => FALSE];
    $options['action_title'] = ['default' => $this->t('Action')];
    $options['selected_actions'] = ['default' => []];
    $options['preconfiguration'] = ['default' => []];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // If the view type is not supported, suppress form display.
    // Also display information note to the user.
    if (empty($this->actions)) {
      $form = [
        '#type' => 'item',
        '#title' => $this->t('NOTE'),
        '#markup' => $this->t('Views Bulk Operations will work only with normal entity views and contrib module views that are integrated. See \Drupal\views_bulk_operations\EventSubscriber\ViewsBulkOperationsEventSubscriber class for integration best practice.'),
        '#prefix' => '<div class="scroll">',
        '#suffix' => '</div>',
      ];
      return;
    }

    $form['#attributes']['class'][] = 'views-bulk-operations-ui';
    $form['#attached']['library'][] = 'views_bulk_operations/adminUi';

    $form['batch'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Process in a batch operation'),
      '#default_value' => $this->options['batch'],
    ];

    $form['batch_size'] = [
      '#title' => $this->t('Batch size'),
      '#type' => 'number',
      '#min' => 1,
      '#step' => 1,
      '#description' => $this->t('Only applicable if results are processed in a batch operation.'),
      '#default_value' => $this->options['batch_size'],
    ];

    $form['form_step'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Configuration form on new page (configurable actions)'),
      '#default_value' => $this->options['form_step'],
      // Due to #2879310 this setting must always be at TRUE.
      '#access' => FALSE,
    ];

    $form['buttons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display selectable actions as buttons.'),
      '#default_value' => $this->options['buttons'],
    ];

    $form['clear_on_exposed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Clear selection when exposed filters change.'),
      '#default_value' => $this->options['clear_on_exposed'],
    ];

    $form['action_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Action title'),
      '#default_value' => $this->options['action_title'],
      '#description' => $this->t('The title shown above the actions dropdown.'),
    ];

    $form['selected_actions'] = [
      '#tree' => TRUE,
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Selected actions'),
      '#attributes' => ['class' => ['vbo-actions-widget']],
    ];

    // Load values for display.
    $form_values = $form_state->getValue(['options', 'selected_actions']);
    if (is_null($form_values)) {
      $selected_actions = $this->options['selected_actions'];
      $preconfiguration = $this->options['preconfiguration'];
    }
    else {
      $selected_actions = [];
      $preconfiguration = [];
      foreach ($form_values as $id => $value) {
        $selected_actions[$id] = $value['state'] ? $id : 0;
        $preconfiguration[$id] = isset($value['preconfiguration']) ? $value['preconfiguration'] : [];
      }
    }

    foreach ($this->actions as $id => $action) {
      $form['selected_actions'][$id]['state'] = [
        '#type' => 'checkbox',
        '#title' => $action['label'],
        '#default_value' => empty($selected_actions[$id]) ? 0 : 1,
        '#attributes' => ['class' => ['vbo-action-state']],
      ];

      // There are problems with AJAX on this form when adding
      // new elements (Views issue), a workaround is to render
      // all elements and show/hide them when needed.
      $form['selected_actions'][$id]['preconfiguration'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Preconfiguration for "@action"', [
          '@action' => $action['label'],
        ]),
        '#attributes' => [
          'data-for' => $id,
          'style' => empty($selected_actions[$id]) ? 'display: none' : NULL,
        ],
      ];

      // Default label_override element.
      $form['selected_actions'][$id]['preconfiguration']['label_override'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Override label'),
        '#description' => $this->t('Leave empty for the default label.'),
        '#default_value' => isset($preconfiguration[$id]['label_override']) ? $preconfiguration[$id]['label_override'] : '',
      ];

      // Load preconfiguration form if available.
      if (method_exists($action['class'], 'buildPreConfigurationForm')) {
        if (!isset($preconfiguration[$id])) {
          $preconfiguration[$id] = [];
        }
        $actionObject = $this->actionManager->createInstance($id);
        $form['selected_actions'][$id]['preconfiguration'] = $actionObject->buildPreConfigurationForm($form['selected_actions'][$id]['preconfiguration'], $preconfiguration[$id], $form_state);
      }
    }

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    $options = &$form_state->getValue('options');
    foreach ($options['selected_actions'] as $id => $action) {
      if (!empty($action['state'])) {
        if (isset($action['preconfiguration'])) {
          $options['preconfiguration'][$id] = $action['preconfiguration'];
          unset($options['selected_actions'][$id]['preconfiguration']);
        }
        $options['selected_actions'][$id] = $id;
      }
      else {
        unset($options['preconfiguration'][$id]);
        $options['selected_actions'][$id] = 0;
      }
    }
    parent::submitOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    parent::preRender($values);

    // Add empty classes if there are no actions available.
    if (empty($this->getBulkOptions())) {
      $this->options['element_label_class'] .= 'empty';
      $this->options['element_class'] .= 'empty';
      $this->options['element_wrapper_class'] .= 'empty';
      $this->options['label'] = '';
    }
    // If the view is using a table style, provide a placeholder for a
    // "select all" checkbox.
    elseif (!empty($this->view->style_plugin) && $this->view->style_plugin instanceof Table) {
      // Add the tableselect css classes.
      $this->options['element_label_class'] .= 'select-all';
      // Hide the actual label of the field on the table header.
      $this->options['label'] = '';
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $row, $field = NULL) {
    return '<!--form-item-' . $this->options['id'] . '--' . $row->index . '-->';
  }

  /**
   * Form constructor for the bulk form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsForm(array &$form, FormStateInterface $form_state) {
    // Make sure we do not accidentally cache this form.
    // @todo Evaluate this again in https://www.drupal.org/node/2503009.
    $form['#cache']['max-age'] = 0;

    // Add VBO class to the form.
    $form['#attributes']['class'][] = 'vbo-view-form';

    // Add VBO front UI and tableselect libraries for table display style.
    if ($this->view->style_plugin instanceof Table) {
      $form['#attached']['library'][] = 'core/drupal.tableselect';
      $this->view->style_plugin->options['views_bulk_operations_enabled'] = TRUE;
    }
    $form['#attached']['library'][] = 'views_bulk_operations/frontUi';
    // Only add the bulk form options and buttons if
    // there are results and any actions are available.
    $action_options = $this->getBulkOptions();
    if (!empty($this->view->result) && !empty($action_options)) {

      // Update tempstore data.
      $this->updateTempstoreData();

      $form[$this->options['id']]['#tree'] = TRUE;

      // Get pager data if available.
      if (!empty($this->view->pager) && method_exists($this->view->pager, 'hasMoreRecords')) {
        $pagerData = [
          'current' => $this->view->pager->getCurrentPage(),
          'more' => $this->view->pager->hasMoreRecords(),
        ];
      }

      // Render checkboxes for all rows.
      $page_selected = [];
      $base_field = $this->view->storage->get('base_field');
      foreach ($this->view->result as $row_index => $row) {
        $entity = $this->getEntity($row);
        $bulk_form_key = self::calculateEntityBulkFormKey(
          $entity,
          $row->{$base_field}
        );

        $checked = isset($this->tempStoreData['list'][$bulk_form_key]);
        if ($checked) {
          $page_selected[] = $bulk_form_key;
        }
        $form[$this->options['id']][$row_index] = [
          '#type' => 'checkbox',
          '#title' => $entity->label(),
          '#title_display' => 'invisible',
          '#default_value' => $checked,
          '#return_value' => $bulk_form_key,
        ];
      }

      // Ensure a consistent container for filters/operations
      // in the view header.
      $form['header'] = [
        '#type' => 'container',
        '#weight' => -100,
      ];

      // Build the bulk operations action widget for the header.
      // Allow themes to apply .container-inline on this separate container.
      $form['header'][$this->options['id']] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'vbo-action-form-wrapper',
        ],
      ];

      // Display actions buttons or selector.
      if ($this->options['buttons']) {
        unset($form['actions']['submit']);
        foreach ($action_options as $id => $label) {
          $form['actions'][$id] = [
            '#type' => 'submit',
            '#value' => $label,
          ];
        }
      }
      else {
        // Replace the form submit button label.
        $form['actions']['submit']['#value'] = $this->t('Apply to selected items');

        $form['header'][$this->options['id']]['action'] = [
          '#type' => 'select',
          '#title' => $this->options['action_title'],
          '#options' => ['' => $this->t('-- Select action --')] + $action_options,
        ];
      }

      // Add AJAX functionality if actions are configurable through this form.
      if (empty($this->options['form_step'])) {
        $form['header'][$this->options['id']]['action']['#ajax'] = [
          'callback' => [__CLASS__, 'viewsFormAjax'],
          'wrapper' => 'vbo-action-configuration-wrapper',
        ];
        $form['header'][$this->options['id']]['configuration'] = [
          '#type' => 'container',
          '#attributes' => ['id' => 'vbo-action-configuration-wrapper'],
        ];

        $action_id = $form_state->getValue('action');
        if (!empty($action_id)) {
          $action = $this->actions[$action_id];
          if ($this->isConfigurable($action)) {
            $actionObject = $this->actionManager->createInstance($action_id);
            $form['header'][$this->options['id']]['configuration'] += $actionObject->buildConfigurationForm($form['header'][$this->options['id']]['configuration'], $form_state);
            $form['header'][$this->options['id']]['configuration']['#config_included'] = TRUE;
          }
        }
      }

      $display_select_all = isset($pagerData) && ($pagerData['more'] || $pagerData['current'] > 0);
      // Selection info: displayed if exposed filters are set and selection
      // is not cleared when they change or "select all" element display
      // conditions are met.
      if ((!$this->options['clear_on_exposed'] && !empty($this->view->getExposedInput())) || $display_select_all) {

        $form['header'][$this->options['id']]['multipage'] = [
          '#type' => 'details',
          '#open' => FALSE,
          '#title' => $this->t('Selected %count items in this view', [
            '%count' => count($this->tempStoreData['list']),
          ]),
          '#attributes' => [
            // Add view_id and display_id to be available for
            // js multipage selector functionality.
            'data-view-id' => $this->tempStoreData['view_id'],
            'data-display-id' => $this->tempStoreData['display_id'],
            'class' => ['vbo-multipage-selector'],
            'name' => 'somename',
          ],
        ];

        // Display a list of items selected on other pages.
        $form['header'][$this->options['id']]['multipage']['list'] = [
          '#theme' => 'item_list',
          '#title' => $this->t('Items selected on other pages:'),
          '#items' => [],
          '#empty' => $this->t('No selection'),
        ];
        if (count($this->tempStoreData['list']) > count($page_selected)) {
          foreach ($this->tempStoreData['list'] as $bulk_form_key => $item) {
            if (!in_array($bulk_form_key, $page_selected)) {
              $form['header'][$this->options['id']]['multipage']['list']['#items'][] = $item[4];
            }
          }
          $form['header'][$this->options['id']]['multipage']['clear'] = [
            '#type' => 'submit',
            '#value' => $this->t('Clear'),
            '#submit' => [[$this, 'clearSelection']],
            '#limit_validation_errors' => [],
          ];
        }
      }

      // Select all results checkbox.
      if ($display_select_all) {
        $form['header'][$this->options['id']]['select_all'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Select all@count results in this view', [
            '@count' => $this->tempStoreData['total_results'] ? ' ' . $this->tempStoreData['total_results'] : '',
          ]),
          '#attributes' => ['class' => ['vbo-select-all']],
        ];
      }

      // Duplicate the form actions into the action container in the header.
      $form['header'][$this->options['id']]['actions'] = $form['actions'];
    }
    else {
      // Remove the default actions build array.
      unset($form['actions']);
    }

  }

  /**
   * AJAX callback for the views form.
   *
   * Currently not used due to #2879310.
   */
  public static function viewsFormAjax(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $plugin_id = $trigger['#array_parents'][1];
    return $form['header'][$plugin_id]['configuration'];
  }

  /**
   * Returns the available operations for this form.
   *
   * @return array
   *   An associative array of operations, suitable for a select element.
   */
  protected function getBulkOptions() {
    if (!isset($this->bulkOptions)) {
      $this->bulkOptions = [];
      foreach ($this->actions as $id => $definition) {
        // Filter out actions that weren't selected.
        if (!in_array($id, $this->options['selected_actions'], TRUE)) {
          continue;
        }

        // Check access permission, if defined.
        if (!empty($definition['requirements']['_permission']) && !$this->currentUser->hasPermission($definition['requirements']['_permission'])) {
          continue;
        }

        // Override label if applicable.
        if (!empty($this->options['preconfiguration'][$id]['label_override'])) {
          $this->bulkOptions[$id] = $this->options['preconfiguration'][$id]['label_override'];
        }
        else {
          $this->bulkOptions[$id] = $definition['label'];
        }
      }
    }

    return $this->bulkOptions;
  }

  /**
   * Submit handler for the bulk form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsFormSubmit(array &$form, FormStateInterface $form_state) {
    if ($form_state->get('step') == 'views_form_views_form') {

      $action_id = $form_state->getValue('action');

      $action = $this->actions[$action_id];

      $this->tempStoreData['action_id'] = $action_id;
      $this->tempStoreData['action_label'] = empty($this->options['preconfiguration'][$action_id]['label_override']) ? (string) $action['label'] : $this->options['preconfiguration'][$action_id]['label_override'];
      $this->tempStoreData['relationship_id'] = $this->options['relationship'];
      $this->tempStoreData['preconfiguration'] = isset($this->options['preconfiguration'][$action_id]) ? $this->options['preconfiguration'][$action_id] : [];

      if (!$form_state->getValue('select_all')) {

        // Update list data with the current form selection.
        foreach ($form_state->getValue($this->options['id']) as $row_index => $bulkFormKey) {
          if ($bulkFormKey) {
            $this->tempStoreData['list'][$bulkFormKey] = $this->getListItem($bulkFormKey, $form[$this->options['id']][$row_index]['#title']);
          }
          else {
            unset($this->tempStoreData['list'][$form[$this->options['id']][$row_index]['#return_value']]);
          }
        }
      }
      else {
        // Unset the list completely.
        $this->tempStoreData['list'] = [];
      }

      $configurable = $this->isConfigurable($action);

      // Get configuration if using AJAX.
      if ($configurable && empty($this->options['form_step'])) {
        $actionObject = $this->actionManager->createInstance($action_id);
        if (method_exists($actionObject, 'submitConfigurationForm')) {
          $actionObject->submitConfigurationForm($form, $form_state);
          $this->tempStoreData['configuration'] = $actionObject->getConfiguration();
        }
        else {
          $form_state->cleanValues();
          $this->tempStoreData['configuration'] = $form_state->getValues();
        }
      }

      // Routing - determine redirect route.
      if ($this->options['form_step'] && $configurable) {
        $redirect_route = 'views_bulk_operations.execute_configurable';
      }
      elseif ($this->options['batch']) {
        if (!empty($action['confirm_form_route_name'])) {
          $redirect_route = $action['confirm_form_route_name'];
        }
      }
      elseif (!empty($action['confirm_form_route_name'])) {
        $redirect_route = $action['confirm_form_route_name'];
      }

      // Redirect if needed.
      if (!empty($redirect_route)) {
        $this->setTempstoreData($this->tempStoreData);

        $form_state->setRedirect($redirect_route, [
          'view_id' => $this->view->id(),
          'display_id' => $this->view->current_display,
        ]);
      }
      // Or process rows here and now.
      else {
        $this->deleteTempstoreData();
        $this->actionProcessor->executeProcessing($this->tempStoreData, $this->view);
        $form_state->setRedirectUrl($this->tempStoreData['redirect_url']);
      }
    }
  }

  /**
   * Clear the form selection along with entire tempstore.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function clearSelection(array &$form, FormStateInterface $form_state) {
    $this->deleteTempstoreData();
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormValidate(&$form, FormStateInterface $form_state) {
    if ($this->options['buttons']) {
      $trigger = $form_state->getTriggeringElement();
      $action_id = end($trigger['#parents']);
      $form_state->setValue('action', $action_id);
    }

    if (empty($form_state->getValue('action'))) {
      $form_state->setErrorByName('action', $this->t('Please select an action to perform.'));
    }

    // This happened once, can't reproduce but here's a safety switch.
    if (!isset($this->actions[$form_state->getValue('action')])) {
      $form_state->setErrorByName('action', $this->t('Form error occurred, please try again.'));
    }

    if (!$form_state->getValue('select_all')) {
      // Update tempstore data to make sure we have also
      // results selected in other requests and validate if
      // anything is selected.
      $this->tempStoreData = $this->getTempstoreData();
      $selected = array_filter($form_state->getValue($this->options['id']));
      if (empty($this->tempStoreData['list']) && empty($selected)) {
        $form_state->setErrorByName('', $this->t('No items selected.'));
      }
    }

    // Action config validation (if implemented).
    if (empty($this->options['form_step']) && !empty($form['header'][$this->options['id']]['configuration']['#config_included'])) {
      $action_id = $form_state->getValue('action');
      $action = $this->actions[$action_id];
      if (method_exists($action['class'], 'validateConfigurationForm')) {
        $actionObject = $this->actionManager->createInstance($action_id);
        $actionObject->validateConfigurationForm($form['header'][$this->options['id']]['configuration'], $form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clickSortable() {
    return FALSE;
  }

  /**
   * Check if an action is configurable.
   */
  protected function isConfigurable($action) {
    return (in_array('Drupal\Core\Plugin\PluginFormInterface', class_implements($action['class']), TRUE) || method_exists($action['class'], 'buildConfigurationForm'));
  }

}
