<?php

namespace Drupal\portland_govdelivery\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Webform handler to subscribe users to GovDelivery topics.
 *
 * @WebformHandler(
 *   id = "portland_govdelivery_subscribe",
 *   label = @Translation("GovDelivery Subscribe"),
 *   category = @Translation("Portland"),
 *   description = @Translation("Subscribe user to GovDelivery topics using the Portland GovDelivery integration."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL
 * )
 */
class GovDeliverySubscribeHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'email_element' => '',
      'topics_source' => 'select',
      'topics' => [],
      'topics_element' => '',
      'question_mappings' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->configuration;

    $form['email_element'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email element machine name'),
      '#description' => $this->t('Enter the machine name of the webform element that contains the subscriber\'s email address.'),
      '#default_value' => $config['email_element'] ?? '',
      '#required' => TRUE,
    ];

    // Topics source selection.
    $form['topics_source'] = [
      '#type' => 'radios',
      '#title' => $this->t('Topics source'),
      '#description' => $this->t('Choose how to determine which topics to subscribe the user to.'),
      '#options' => [
        'select' => $this->t('Select topics from list'),
        'element' => $this->t('Get topics from form element'),
      ],
      '#default_value' => $config['topics_source'] ?? 'select',
      '#required' => TRUE,
    ];

    // Load topics from the TopicsProvider service.
    $options = [];
    try {
      /** @var \Drupal\portland_govdelivery\Service\TopicsProvider $topics_provider */
      $topics_provider = \Drupal::service('portland_govdelivery.topics_provider');
      $options = $topics_provider->getWebformOptions();
    }
    catch (\Throwable $e) {
      $options = [];
      \Drupal::logger('portland_govdelivery')->error('Unable to load GovDelivery topics for handler config: @msg', ['@msg' => $e->getMessage()]);
    }

    // Note: Removed temporary generation of test topics.

    $form['topics'] = [
      '#type' => 'select',
      '#title' => $this->t('Topics'),
      '#description' => $this->t('Select the GovDelivery topic(s) to subscribe the user to. Hold Ctrl/Cmd to select multiple.'),
      '#options' => $options,
      '#default_value' => $config['topics'] ?? [],
      '#multiple' => TRUE,
      '#size' => 5,
      '#states' => [
        'visible' => [
          ':input[name="settings[topics_source]"]' => ['value' => 'select'],
        ],
        'required' => [
          ':input[name="settings[topics_source]"]' => ['value' => 'select'],
        ],
      ],
    ];

    $form['topics_element'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Topics element machine name'),
      '#description' => $this->t('Enter the machine name of the webform element that contains topic code(s). The element can contain a single topic code or multiple codes. For multiple values, separate topic codes with commas, pipes (|), or newlines. Example: "ORPORTLAND_ENT_BUDGET_FINANCE,ORPORTLAND_ENT_DISTRICT_1"'),
      '#default_value' => $config['topics_element'] ?? '',
      '#states' => [
        'visible' => [
          ':input[name="settings[topics_source]"]' => ['value' => 'element'],
        ],
        'required' => [
          ':input[name="settings[topics_source]"]' => ['value' => 'element'],
        ],
      ],
    ];

    // Question mappings - manual configuration.
    $form['question_mappings'] = [
      '#type' => 'details',
      '#title' => $this->t('Question mappings'),
      '#description' => $this->t('Map webform fields to GovDelivery custom question codes.'),
      '#open' => TRUE,
    ];

    // Build webform field options.
    $webform_fields = $this->getWebform()->getElementsInitializedFlattenedAndHasValue();
    $field_options = ['' => '- ' . $this->t('None') . ' -'];
    foreach ($webform_fields as $key => $value) {
      $title = $value['#admin_title'] ?? $value['#title'] ?? NULL;
      if (empty($title)) continue;
      if (array_key_exists('#webform_composite_elements', $value)) {
        foreach ($value['#webform_composite_elements'] as $composite_element) {
          if (isset($composite_element['#webform_composite_key'])) {
            $composite_key = $composite_element['#webform_composite_key'];
            $field_options[$composite_key] = $title . ' > ' . ($composite_element['#title'] ?? $composite_key);
          }
        }
      }
      else {
        $field_options[$key] = $title;
      }
    }

    // Get existing mappings or create default empty rows.
    $mappings = $config['question_mappings'] ?? [];
    $num_mappings = max(3, count($mappings));

    $form['question_mappings']['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Webform field'),
        $this->t('GovDelivery question code'),
        $this->t('Operations'),
      ],
      '#prefix' => '<div id="question-mappings-wrapper">',
      '#suffix' => '</div>',
    ];

    // Add rows for existing mappings plus empty rows to reach minimum of 3.
    $row_index = 0;
    foreach ($mappings as $question_code => $field_key) {
      $form['question_mappings']['table'][$row_index]['field'] = [
        '#type' => 'select',
        '#options' => $field_options,
        '#default_value' => $field_key,
        '#empty_option' => '- None -',
      ];
      $form['question_mappings']['table'][$row_index]['question_code'] = [
        '#type' => 'textfield',
        '#default_value' => $question_code,
        '#size' => 40,
        '#maxlength' => 255,
        '#placeholder' => $this->t('e.g., QUESTION_CODE_123'),
      ];
      $form['question_mappings']['table'][$row_index]['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => 'remove_mapping_' . $row_index,
        '#submit' => ['::removeMapping'],
        '#ajax' => [
          'callback' => '::ajaxRefreshMappings',
          'wrapper' => 'question-mappings-wrapper',
        ],
        '#limit_validation_errors' => [],
        '#row_index' => $row_index,
      ];
      $row_index++;
    }

    // Add empty rows to reach minimum of 3.
    while ($row_index < $num_mappings) {
      $form['question_mappings']['table'][$row_index]['field'] = [
        '#type' => 'select',
        '#options' => $field_options,
        '#empty_option' => '- None -',
      ];
      $form['question_mappings']['table'][$row_index]['question_code'] = [
        '#type' => 'textfield',
        '#size' => 40,
        '#maxlength' => 255,
        '#placeholder' => $this->t('e.g., QUESTION_CODE_123'),
      ];
      $form['question_mappings']['table'][$row_index]['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => 'remove_mapping_' . $row_index,
        '#submit' => ['::removeMapping'],
        '#ajax' => [
          'callback' => '::ajaxRefreshMappings',
          'wrapper' => 'question-mappings-wrapper',
        ],
        '#limit_validation_errors' => [],
        '#row_index' => $row_index,
      ];
      $row_index++;
    }

    $form['question_mappings']['add_more'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another mapping'),
      '#submit' => ['::addMoreMapping'],
      '#ajax' => [
        'callback' => '::ajaxRefreshMappings',
        'wrapper' => 'question-mappings-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * Submit handler to add another mapping row.
   */
  public function addMoreMapping(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Submit handler to remove a mapping row.
   */
  public function removeMapping(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * AJAX callback to refresh the mappings table.
   */
  public function ajaxRefreshMappings(array &$form, FormStateInterface $form_state) {
    return $form['settings']['question_mappings']['table'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $email_element = trim((string) $form_state->getValue('email_element'));
    if ($email_element === '') {
      $form_state->setErrorByName('email_element', $this->t('Email element machine name is required.'));
    }

    $topics_source = $form_state->getValue('topics_source');
    if ($topics_source === 'select') {
      $topics = (array) $form_state->getValue('topics');
      if (empty($topics)) {
        $form_state->setErrorByName('topics', $this->t('You must select at least one topic.'));
      }
    }
    elseif ($topics_source === 'element') {
      $topics_element = trim((string) $form_state->getValue('topics_element'));
      if ($topics_element === '') {
        $form_state->setErrorByName('topics_element', $this->t('Topics element machine name is required.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['email_element'] = trim((string) $form_state->getValue('email_element'));
    $this->configuration['topics_source'] = $form_state->getValue('topics_source');
    $this->configuration['topics'] = (array) $form_state->getValue('topics');
    $this->configuration['topics_element'] = trim((string) $form_state->getValue('topics_element'));
    
    // Process question mappings from table.
    $mappings = [];
    $table_values = $form_state->getValue(['question_mappings', 'table']) ?? [];
    foreach ($table_values as $row) {
      $field = $row['field'] ?? '';
      $question_code = trim($row['question_code'] ?? '');
      if ($field !== '' && $question_code !== '') {
        $mappings[$question_code] = $field;
      }
    }
    $this->configuration['question_mappings'] = $mappings;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $data = $webform_submission->getData();
    $email_element = (string) ($this->configuration['email_element'] ?? '');
    $topics_source = (string) ($this->configuration['topics_source'] ?? 'select');

    $sid = $webform_submission->id();
    $webform_id = $webform_submission->getWebform()->id();

    // Get email from submission data.
    if ($email_element === '' || !array_key_exists($email_element, $data)) {
      \Drupal::logger('portland_govdelivery')->error('GovDelivery handler: Email element %element not found on submission %sid for webform %wid.', [
        '%element' => $email_element,
        '%sid' => $sid,
        '%wid' => $webform_id,
      ]);
      return;
    }

    $email_val = $data[$email_element];
    // If the element stores an array, best effort to extract a string.
    if (is_array($email_val)) {
      $email_val = reset($email_val);
    }
    $email = trim((string) $email_val);

    // Get topics based on source.
    $topics = [];
    if ($topics_source === 'select') {
      $topics = (array) ($this->configuration['topics'] ?? []);
    }
    elseif ($topics_source === 'element') {
      $topics_element = (string) ($this->configuration['topics_element'] ?? '');
      if ($topics_element !== '' && array_key_exists($topics_element, $data)) {
        $topics = $this->parseTopicsFromElement($data[$topics_element]);
      }
      else {
        \Drupal::logger('portland_govdelivery')->error('GovDelivery handler: Topics element %element not found on submission %sid for webform %wid.', [
          '%element' => $topics_element,
          '%sid' => $sid,
          '%wid' => $webform_id,
        ]);
        return;
      }
    }

    if ($email === '' || empty($topics)) {
      \Drupal::logger('portland_govdelivery')->error('GovDelivery handler: Missing email or topics on submission %sid for webform %wid.', [
        '%sid' => $sid,
        '%wid' => $webform_id,
      ]);
      return;
    }

    // Build question answers from mappings.
    $answers = [];
    $question_mappings = $this->configuration['question_mappings'] ?? [];
    foreach ($question_mappings as $question_code => $field_key) {
      if (!array_key_exists($field_key, $data)) {
        continue;
      }
      
      $field_value = $data[$field_key];
      
      // Handle composite fields (e.g., address__city).
      if (strpos($field_key, '__') !== false) {
        $parts = explode('__', $field_key);
        $parent_key = $parts[0];
        $child_key = $parts[1];
        if (isset($data[$parent_key][$child_key])) {
          $field_value = $data[$parent_key][$child_key];
        }
      }
      
      // Convert arrays to comma-separated string.
      if (is_array($field_value)) {
        $field_value = implode(', ', array_filter($field_value));
      }
      
      $field_value = trim((string) $field_value);
      if ($field_value !== '') {
        $answers[$question_code] = $field_value;
      }
    }

    try {
      /** @var \Drupal\portland_govdelivery\Service\GovDeliveryClient $client */
      $client = \Drupal::service('portland_govdelivery.client');
      // Pass answers as the 4th parameter.
      $result = $client->subscribeUser($email, $topics, NULL, $answers);
      
      $log_context = [
        '%email' => $email,
        '%topics' => implode(', ', $topics),
        '%sid' => $sid,
        '%wid' => $webform_id,
      ];
      
      if (!empty($answers)) {
        $log_context['%answers'] = json_encode($answers);
        \Drupal::logger('portland_govdelivery')->info('GovDelivery handler: Subscribed %email to %topics with answers %answers from submission %sid (webform %wid).', $log_context);
      }
      else {
        \Drupal::logger('portland_govdelivery')->info('GovDelivery handler: Subscribed %email to %topics from submission %sid (webform %wid).', $log_context);
      }
    }
    catch (\Throwable $e) {
      \Drupal::logger('portland_govdelivery')->error('GovDelivery handler: Failed subscribing %email on submission %sid (webform %wid): @msg', [
        '%email' => $email,
        '%sid' => $sid,
        '%wid' => $webform_id,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Parse topic codes from element value.
   * 
   * Supports single value, arrays, or delimited strings (comma, pipe, newline).
   * 
   * @param mixed $value
   *   The element value from submission data.
   * 
   * @return array
   *   Array of topic codes.
   */
  protected function parseTopicsFromElement($value): array {
    $topics = [];

    // Handle array values (e.g., from checkboxes, select multiple).
    if (is_array($value)) {
      foreach ($value as $item) {
        $parsed = $this->parseTopicsFromElement($item);
        $topics = array_merge($topics, $parsed);
      }
      return array_values(array_filter(array_unique($topics)));
    }

    // Handle string values - split by common delimiters.
    $str = trim((string) $value);
    if ($str === '') {
      return [];
    }

    // Split by comma, pipe, or newline.
    $codes = preg_split('/[,|\n\r]+/', $str);
    foreach ($codes as $code) {
      $code = trim($code);
      if ($code !== '') {
        $topics[] = $code;
      }
    }

    return array_values(array_filter(array_unique($topics)));
  }
}
