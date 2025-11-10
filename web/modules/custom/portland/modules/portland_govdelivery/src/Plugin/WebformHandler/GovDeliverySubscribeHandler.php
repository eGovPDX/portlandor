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
      // Map GovDelivery question IDs (to-param) to webform element machine names.
      'question_element_map' => [],
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

    // Build question mapping list (A: global questions + B: questions for selected topics).
    $question_rows = [];
    try {
      /** @var \Drupal\portland_govdelivery\Service\QuestionsProvider $qp */
      $qp = \Drupal::service('portland_govdelivery.questions_provider');
      $all_questions = $qp->getAllQuestions();

      // Get all elements from the current webform for mapping.
      $webform = $this->getWebform();
      $elements = $webform ? $webform->getElementsInitializedFlattenedAndHasValue() : [];
      $element_options = [];
      foreach ($elements as $key => $element) {
        // Use title if available, otherwise key.
        $label = isset($element['#title']) ? strip_tags($element['#title']) : $key;
        $element_options[$key] = $label . ' [' . $key . ']';
      }

      // Compute current topic selection from form state (unsaved) or config (saved).
      $selected_topics = [];
      $current_source = $form_state->getValue('topics_source') ?? ($config['topics_source'] ?? 'select');
      if ($current_source === 'select') {
        $selected_topics = (array) ($form_state->getValue('topics') ?? ($config['topics'] ?? []));
      }

      // Filter: include global (no topics) always, plus any whose topics intersect selected.
      $filtered = [];
      foreach ($all_questions as $qid => $q) {
        $qt = $q['topics'] ?? [];
        $is_global = empty($qt);
        $in_selected = !empty(array_intersect($qt, $selected_topics));
        if ($is_global || $in_selected) {
          $filtered[$qid] = $q;
        }
      }
      // Stable sort by name then id.
      uasort($filtered, function($a, $b) {
        $c = strcasecmp($a['name'] ?? '', $b['name'] ?? '');
        if ($c !== 0) { return $c; }
        return ($a['id'] ?? '') <=> ($b['id'] ?? '');
      });

      // Existing mappings.
      $existing_map = (array) ($config['question_element_map'] ?? []);

        foreach ($filtered as $qid => $q) {
          $name = $q['name'] ?? $qid;
          $topics_list = empty($q['topics']) ? $this->t('Global') : implode(', ', $q['topics']);
          // Only set default_value if a mapping exists, otherwise leave it empty so the empty option is selected.
          $default_value = isset($existing_map[$qid]) && $existing_map[$qid] !== '' ? $existing_map[$qid] : '';
          $question_rows[$qid] = [
            'question' => ['data' => ['#markup' => '<strong>' . htmlspecialchars($name) . '</strong><br/><small>' . htmlspecialchars($q['question_text'] ?? '') . '</small>']],
            'topics' => ['data' => ['#markup' => htmlspecialchars($topics_list)]],
            'element' => [
              'data' => [
                '#type' => 'select',
                '#options' => ['' => $this->t('Choose...')] + $element_options,
                // Use explicit name/value so the element posts like Smartsheet handler.
                '#name' => "settings[question_element_map][$qid]",
                '#value' => $default_value,
                '#attributes' => [
                  'style' => 'max-width: 300px;',
                ],
                '#required' => FALSE,
              ],
            ],
          ];
        }
    }
    catch (\Throwable $e) {
      // Log and proceed without mapping table.
      \Drupal::logger('portland_govdelivery')->error('Unable to build question mapping table: @msg', ['@msg' => $e->getMessage()]);
    }

    $form['question_mappings'] = [
      '#type' => 'details',
      '#title' => $this->t('GovDelivery question to element mappings'),
      '#description' => $this->t('Map GovDelivery questions to webform element machine names. Only global questions and those associated with the selected topics are shown. Leave blank to skip sending a response for a question.'),
      '#open' => FALSE,
    ];

    if (!empty($question_rows)) {
      $form['question_mappings']['table'] = [
        '#type' => 'table',
        '#header' => [
          'question' => $this->t('Question'),
          'topics' => $this->t('Topics'),
          'element' => $this->t('Webform element'),
        ],
        '#rows' => $question_rows,
        '#attributes' => ['class' => ['govdelivery-questions-map-table']],
        '#tree' => TRUE,
      ];
    }
    else {
      $form['question_mappings']['empty'] = [
        '#markup' => '<p><em>' . $this->t('No questions available for mapping (check topics selection or admin cache).') . '</em></p>',
      ];
    }

    return parent::buildConfigurationForm($form, $form_state);
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
    // Persist question mappings.
    // Read mappings directly from user input (explicit names on selects).
    $user_input = $form_state->getUserInput();
    $settings = $user_input['settings'] ?? [];
    $raw_map = $settings['question_element_map'] ?? [];
    if (!is_array($raw_map)) {
      $raw_map = [];
    }
    // Filter out empty values.
    $map = [];
    foreach ($raw_map as $qid => $element_key) {
      $element_key = trim((string) $element_key);
      if ($element_key !== '') {
        $map[$qid] = $element_key;
      }
    }
    $this->configuration['question_element_map'] = $map;
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

    try {
      /** @var \Drupal\portland_govdelivery\Service\GovDeliveryClient $client */
      $client = \Drupal::service('portland_govdelivery.client');
      $result = $client->subscribeUser($email, $topics);
      
      \Drupal::logger('portland_govdelivery')->info('GovDelivery handler: Subscribed %email to %topics from submission %sid (webform %wid).', [
        '%email' => $email,
        '%topics' => implode(', ', $topics),
        '%sid' => $sid,
        '%wid' => $webform_id,
      ]);
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

  /**
   * AJAX callback to rebuild the question mappings panel.
   */
  public function ajaxRebuildQuestionMappings(array &$form, FormStateInterface $form_state) {
    // Ensure the element exists before returning.
    if (isset($form['question_mappings'])) {
      return $form['question_mappings'];
    }
    // Fallback: return empty response if element not built yet.
    return ['#markup' => ''];
  }
}
