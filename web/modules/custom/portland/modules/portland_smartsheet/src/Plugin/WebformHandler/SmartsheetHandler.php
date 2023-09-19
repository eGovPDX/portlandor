<?php

namespace Drupal\portland_smartsheet\Plugin\WebformHandler;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformMessageManagerInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\file\Entity\File;
use Drupal\webform\Entity\Webform;
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
  protected WebformElementManagerInterface $elementManager;
  protected WebformMessageManagerInterface $messageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->elementManager = $container->get('plugin.manager.webform.element');
    $instance->messageManager = $container->get('webform.message_manager');
    return $instance;
  }

  /**
   * Provides list of setting fields
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'column_mappings' => [],
      'multiple_rows_enable' => false,
      'multiple_rows_field' => '',
      'multiple_rows_separator' => '',
      'row_location' => 'toBottom',
      'sheet_id' => '',
      'upload_attachments' => true,
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

  private function getAttachments(WebformSubmissionInterface $webform_submission) {
    $attachments = [];
    $elements = $this->getWebform()->getElementsInitializedAndFlattened();
    $element_attachments = $this->getWebform()->getElementsAttachments();
    foreach ($element_attachments as $element_attachment) {
      $element = $elements[$element_attachment];
      $element_plugin = $this->elementManager->getElementInstance($element);
      $attachments = array_merge($attachments, $element_plugin->getEmailAttachments($element, $webform_submission));
    }

    return $attachments;
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
          ['data' => ['#markup' => '<strong>' . htmlentities($col->title) . '</strong>']],
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

    $form['multiple_rows_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create multiple rows'),
      '#description' => $this->t('If checked, you can choose a field which will be the key when creating multiple rows.'),
      '#description_display' => 'before',
      '#default_value' => $this->configuration['multiple_rows_enable'],
    ];

    $form['multiple_rows_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Multiple rows field'),
      '#description' => $this->t('This field will be used to decide whether to create multiple rows. If it contains more than one element, a new row will be created for each element.'),
      '#description_display' => 'before',
      '#default_value' => $this->configuration['multiple_rows_field'],
      '#states' => [
        'visible' => [
          ':input[name="settings[multiple_rows_enable]"]' => ['checked' => true],
        ],
      ],
    ];

    $form['multiple_rows_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Multiple rows separator'),
      '#description' => $this->t('If the field is NOT an array, this separator will be used to split the data into an array.'),
      '#description_display' => 'before',
      '#default_value' => $this->configuration['multiple_rows_separator'],
      '#states' => [
        'visible' => [
          ':input[name="settings[multiple_rows_enable]"]' => ['checked' => true],
        ],
      ],
    ];

    $form['row_location'] = [
      '#type' => 'select',
      '#title' => $this->t('Row location'),
      '#description' => $this->t('Where the new rows should be added in the sheet.'),
      '#description_display' => 'before',
      '#default_value' => $this->configuration['row_location'],
      '#options' => [
        'toBottom' => 'To Bottom',
        'toTop' => 'To Top',
      ],
    ];

    $form['upload_attachments'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Upload attachments to row'),
      '#description' => $this->t('If checked, any attachments in the webform submission data will be uploaded to the first row.'),
      '#description_display' => 'before',
      '#default_value' => $this->configuration['upload_attachments'],
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
    $this->configuration['multiple_rows_enable'] = $values['multiple_rows_enable'];
    $this->configuration['multiple_rows_field'] = $values['multiple_rows_field'];
    $this->configuration['multiple_rows_separator'] = $values['multiple_rows_separator'];
    $this->configuration['row_location'] = $values['row_location'];
    $this->configuration['sheet_id'] = $values['sheet_id'];
    $this->configuration['upload_attachments'] = $values['upload_attachments'];
  }

  private function getRowData(array $fields) {
    $column_mappings = $this->configuration['column_mappings'];
    $row_location = $this->configuration['row_location'];
    $cells = [];
    foreach ($column_mappings as $col_id => $field_id) {
      // skip empty mappings
      if ($field_id === "") continue;

      $field_data = $fields[$field_id];
      $cells[] = [
        'columnId' => (int) $col_id,
        'value' => is_array($field_data) ? join(',', $field_data) : $field_data,
      ];
    }

    return [
      'cells' => $cells,
      $row_location => true,
    ];
  }

  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // By submitting to the API during the validate phase, we can interrupt the form submission if any errors happen.
    // But we need to check that the validation was triggered by the submit button, and that there were also no validation errors.
    if ($form_state->hasAnyErrors() || !$form_state->getTriggeringElement() || $form_state->getTriggeringElement()['#parents'][0] !== "submit") return;

    $this->messageManager->setWebformSubmission($webform_submission);

    $submission_arr = $webform_submission->toArray(TRUE);
    $submission_id = $submission_arr['sid'] !== '' ? $submission_arr['sid'] : $submission_arr['uuid'];
    $fields = [
      ...$submission_arr['data'],
      '__submission_id' => $submission_id,
    ];

    $multiple_rows_enable = $this->configuration['multiple_rows_enable'];
    $multiple_rows_field = $this->configuration['multiple_rows_field'];
    $multiple_rows_separator = $this->configuration['multiple_rows_separator'];
    $rows = [];
    // if multi-row is enabled, we have to get a new row for each element in the field
    if ($multiple_rows_enable && array_key_exists($multiple_rows_field, $fields)) {
      $field = $fields[$multiple_rows_field];
      // if the field data is an array, just use that. if not, we need to split it using the provided separator
      $field_arr = is_array($field) ? $field : explode($multiple_rows_separator, $field);
      foreach ($field_arr as $multiple_rows_field_data) {
        // copy the fields array, but overwrite the multi-row field with the current array element
        $fields_for_this_row = [
          ...$fields,
          $multiple_rows_field => $multiple_rows_field_data,
        ];
        $rows[] = $this->getRowData($fields_for_this_row);
      }
    } else {
      $rows[] = $this->getRowData($fields);
    }

    try {
      $client = new SmartsheetClient($this->configuration['sheet_id']);
      $rows_result = $client->addRows($rows);

      if ($this->configuration['upload_attachments']) {
        $first_row_id = $rows_result[0]->id;
        $element_attachments = $this->getAttachments($webform_submission);
        foreach ($element_attachments as $attachment) {
          $client->addAttachment($first_row_id, $attachment);
        }
      }
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
      $this->messageManager->display(WebformMessageManagerInterface::SUBMISSION_EXCEPTION_MESSAGE, 'error');

      // Add validation error to prevent submission.
      $form_state->setErrorByName('');
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
