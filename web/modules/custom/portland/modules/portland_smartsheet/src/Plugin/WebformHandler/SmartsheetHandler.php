<?php

namespace Drupal\portland_smartsheet\Plugin\WebformHandler;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\file\Entity\File;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformMessageManagerInterface;
use Drupal\webform\WebformSubmissionForm;
use Drupal\portland_smartsheet\Client\SmartsheetClient;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Form submission to Smartsheet handler.
 *
 * @WebformHandler(
 *   id = "smartsheet_add_row",
 *   label = @Translation("Smartsheet: add row to existing sheet"),
 *   category = @Translation("Smartsheet"),
 *   description = @Translation("Adds a row with webform submission data to an existing Smartsheet"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class SmartsheetHandler extends WebformHandlerBase {
  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $token_manager;

  /**
   * The webform message manager.
   *
   * @var \Drupal\webform\WebformMessageManagerInterface
   */
  protected $message_manager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->token_manager = $container->get('webform.token_manager');
    $instance->message_manager = $container->get('webform.message_manager');
    return $instance;
  }

  /**
   * Provides list of setting fields
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'column_mappings' => [],
      'sheet_id' => ''
    ];
  }

  /**
   * @return array
   */
  public function defaultConfigurationNames() {
    return array_keys($this->defaultConfiguration());
  }

  private function getWebformFields() {
    return $this->getWebform()->getElementsInitializedFlattenedAndHasValue();
  }

  public function fetchColumns(array &$form, FormStateInterface $form_state) {
    $sheet_id = $form_state->getUserInput()['settings']['sheet_id'] ?? $this->configuration["sheet_id"];
    $form['column_mappings'] = ['#type' => 'value'];
    $form['column_mappings_container'] = [
      '#type' => 'details',
      '#title' => $this->t('Column mappings'),
      '#prefix' => '<div id="column-mappings">',
      '#suffix' => '</div>',
      '#open' => TRUE,
      'table' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Smartsheet column'),
          $this->t('Webform field'),
        ],
        '#rows' => []
      ]
    ];
    try {
      if ($sheet_id === '') return;
      $client = new SmartsheetClient($sheet_id);

      $columns = $client->listAllColumns();
      $webform_fields = $this->getWebformFields();
      $webform_fields['__submission_id'] = ['#title' => 'Submission ID'];
      $options = ['' => 'None'];
      foreach ($webform_fields as $key => $value) {
        $title = $value['#admin_title'] ?? $value['#title'] ?? NULL;
        if (empty($title)) continue;

        $options[$key] = $title;
      }

      $form['column_mappings_container']['table']['#rows'] = array_map(
        fn($col) => [
          ['data' => ['#markup' => "<strong>{$col->title}</strong>"]],
          ['data' => [
            '#type' => 'select',
            '#name' => "settings[column_mappings][{$col->id}]",
            '#value' => $this->configuration['column_mappings'][$col->id] ?? '',
            '#options' => $options
          ]]
        ], array_filter($columns, fn($col) => !isset($col->formula))
      );
    } catch (\Exception $e) {
      // Log error message.
      $this->getLogger()->error('@form fetching columns from Smartsheet failed. @exception: @message.', [
        '@exception' => get_class($e),
        '@form' => $this->getWebform()->label(),
        '@message' => $e->getMessage(),
        'link' => $this->getWebform()->toLink($this->t('Edit'), 'handlers')->toString(),
      ]);
      $form['column_mappings_container']['#prefix'] .= '<div class="messages messages--error"><div class="messages__header"><h2 class="messages__title">Error fetching sheet data. Is the sheet ID correct?</h2></div></div>';
    }

    $res = new AjaxResponse();
    $res->addCommand(new ReplaceCommand('#column-mappings', $form['column_mappings_container']));

    return $res;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['sheet_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sheet ID'),
      '#description' => $this->t('Smartsheet sheet ID to use'),
      '#description_display' => 'before',
      '#default_value' => $this->configuration['sheet_id'],
      '#required' => true,
      '#ajax' => [
        'callback' => [$this, 'fetchColumns'],
        'event' => 'change'
      ]
    ];

    $this->fetchColumns($form, $form_state);

    return $this->setSettingsParents($form);
  }

  /**
   * Saves handler settings to config
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getUserInput()['settings'];
    $this->configuration['column_mappings'] = $values['column_mappings'];
    $this->configuration['sheet_id'] = $values['sheet_id'];
  }

  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $this->message_manager->setWebformSubmission($webform_submission);

    $submission_fields = $webform_submission->toArray(TRUE);
    $submission_id = $submission_fields['sid'] !== '' ? $submission_fields['sid'] : $submission_fields['uuid'];

    $column_mappings = $this->configuration['column_mappings'];
    $cells = [];
    foreach ($column_mappings as $col_id => $field_id) {
      if ($field_id === "") continue;

      $field_data = $field_id === '__submission_id' ? $submission_id : $submission_fields['data'][$field_id];
      $cells[] = [
        'columnId' => (int) $col_id,
        'value' => is_array($field_data) ? join(",", $field_data) : $field_data
      ];
    }

    try {
      $client = new SmartsheetClient($this->configuration['sheet_id']);
      $client->addRow([
        'cells' => $cells
      ]);
    } catch (\Exception $e) {
      // Log error message.
      $this->getLogger()->error('@form webform submission to Smartsheet (@handler_id) failed. @exception: @message.', [
        '@exception' => get_class($e),
        '@form' => $this->getWebform()->label(),
        '@message' => $e->getMessage(),
        '@handler_id' => $this->getHandlerId(),
        'link' => $this->getWebform()->toLink($this->t('Edit'), 'handlers')->toString(),
      ]);

      // Display error to user.
      $this->message_manager->display(WebformMessageManagerInterface::SUBMISSION_EXCEPTION_MESSAGE, 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $amt_mappings = count(array_filter($this->configuration['column_mappings'], fn($mapping) => $mapping !== ''));
    $lines = ["{$amt_mappings} column mappings configured"];
    try {
      $client = new SmartsheetClient($this->configuration['sheet_id']);
      $res = $client->getSheet(['columnIds' => 0, 'rowIds' => 0]);

      $sheet_link = htmlentities($res->permalink);
      $sheet_name = htmlentities($res->name);
      $lines[] = "<strong>Sheet:</strong> <a href=\"{$sheet_link}\">{$sheet_name}</a>";
    } catch (\Exception $e) {}

    return [
      '#markup' => join("<br>", $lines)
    ];
  }
}
