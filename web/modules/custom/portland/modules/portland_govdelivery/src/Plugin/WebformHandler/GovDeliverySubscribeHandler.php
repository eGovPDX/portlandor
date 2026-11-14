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

    // Build element options for selects (email + question mappings) early.
    $webform_for_email = $this->getWebform();
    $elements_for_email = $webform_for_email ? $webform_for_email->getElementsInitializedFlattenedAndHasValue() : [];
    $email_element_options = ['' => $this->t('Choose...')];
    foreach ($elements_for_email as $ekey => $element) {
      $label = isset($element['#title']) ? strip_tags($element['#title']) : $ekey;
      $email_element_options[$ekey] = $label . ' [' . $ekey . ']';
    }

    // Group basic subscription settings (email + topics) in a container to ensure
    // they display before the question mappings panel.
    $form['subscription_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Subscription settings'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    $form['subscription_settings']['email_element'] = [
      '#type' => 'select',
      '#title' => $this->t('Email element'),
      '#description' => $this->t('Select the webform element that contains the subscriber\'s email address.'),
      '#options' => $email_element_options,
      '#default_value' => $config['email_element'] ?? '',
      '#required' => TRUE,
    ];

    // Topics settings panel (nested under subscription settings, must appear above mappings).
    $form['subscription_settings']['topics_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('GovDelivery topics'),
      '#open' => TRUE,
    ];

    // Topics source selection.
    $form['subscription_settings']['topics_settings']['topics_source'] = [
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

    $form['subscription_settings']['topics_settings']['topics'] = [
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

    $form['subscription_settings']['topics_settings']['topics_element'] = [
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

    // Build question mappings using helper method.
    $this->buildQuestionMappings($form, $form_state);

    // Force question mappings to the bottom explicitly by re-appending and applying
    // a high weight. This guards against any unexpected reordering logic.
    if (isset($form['zz_question_mappings'])) {
      $qm = $form['zz_question_mappings'];
      unset($form['zz_question_mappings']);
      $qm['#weight'] = 1000; // Very high weight to ensure it is last.
      $form['zz_question_mappings'] = $qm;
    }

    return $this->setSettingsParents($form);
  }

  /**
   * Build question mappings section.
   */
  protected function buildQuestionMappings(array &$form, FormStateInterface $form_state) {
    $config = $this->configuration;
    
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

        // Row 1: Question + Topics (no bottom border)
        $question_rows[] = [
          'data' => [
            ['data' => ['#markup' => '<strong>' . htmlspecialchars($name) . '</strong><br/><small>' . htmlspecialchars($q['question_text'] ?? '') . '</small>'], 'class' => ['gd-question-cell']],
            ['data' => ['#markup' => htmlspecialchars($topics_list)], 'class' => ['gd-topics-cell']],
          ],
          'no_striping' => TRUE,
          'class' => ['gd-question-row'],
        ];

        // Row 2: Webform element selector stacked beneath, spanning both columns (with bottom border to separate blocks)
        $question_rows[] = [
          'data' => [
            [
              'data' => [
                '#type' => 'select',
                '#options' => ['' => $this->t('Choose a webform element...')] + $element_options,
                // Use explicit name/value so the element posts like Smartsheet handler.
                '#name' => "settings[question_element_map][$qid]",
                '#value' => $default_value,
                '#attributes' => [
                  'style' => 'width:100%; max-width: 100%;',
                ],
                '#required' => FALSE,
              ],
              'colspan' => 2,
              'class' => ['gd-mapping-element-cell'],
            ],
          ],
          'no_striping' => TRUE,
          'class' => ['gd-element-row'],
        ];
      }
    }
    catch (\Throwable $e) {
      // Log and proceed without mapping table.
      \Drupal::logger('portland_govdelivery')->error('Unable to build question mapping table: @msg', ['@msg' => $e->getMessage()]);
    }

    // Add question mappings section to form. Rename key with 'zz_' prefix so lexical ordering
    // (if applied by parent logic) keeps it at the bottom. (#weight also set later.)
    $form['zz_question_mappings'] = [
      '#type' => 'details',
      '#title' => $this->t('GovDelivery question to element mappings'),
      '#description' => $this->t('Map GovDelivery questions to webform element machine names. Only global questions and those associated with the selected topics are shown. Leave blank to skip sending a response for a question.'),
      '#open' => FALSE,
    ];

    if (!empty($question_rows)) {
      $form['zz_question_mappings']['table'] = [
        '#type' => 'table',
        '#header' => [
          'question' => $this->t('Question'),
          'topics' => $this->t('Topics'),
        ],
        '#rows' => $question_rows,
        '#attributes' => ['class' => ['govdelivery-questions-map-table']],
        '#attached' => [
          'library' => ['portland_govdelivery/handler_admin'],
        ],
      ];
    }
    else {
      $form['zz_question_mappings']['empty'] = [
        '#markup' => '<p><em>' . $this->t('No questions available for mapping (check topics selection or admin cache).') . '</em></p>',
      ];
    }
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

    // Build question answers array from mapped elements.
    $answers = [];
    $question_map = (array) ($this->configuration['question_element_map'] ?? []);
    
    // Build a lookup from question ID to question name for the answers array.
    // GovDeliveryClient::updateSubscriberResponses expects question names as keys.
    $all_questions = [];
    try {
      /** @var \Drupal\portland_govdelivery\Service\QuestionsProvider $qp */
      $qp = \Drupal::service('portland_govdelivery.questions_provider');
      $all_questions = $qp->getAllQuestions();
    }
    catch (\Throwable $e) {
      \Drupal::logger('portland_govdelivery')->error('GovDelivery handler: Failed to load questions for mapping: @msg', ['@msg' => $e->getMessage()]);
    }
    
    foreach ($question_map as $question_id => $element_key) {
      if ($element_key === '' || !array_key_exists($element_key, $data)) {
        // Skip unmapped or missing elements.
        continue;
      }
      
      // Look up the question name from the question ID.
      $question_name = NULL;
      $question_def = NULL;
      if (isset($all_questions[$question_id])) {
        $question_def = $all_questions[$question_id];
        $question_name = $question_def['name'] ?? NULL;
      }
      
      if (!$question_name) {
        \Drupal::logger('portland_govdelivery')->warning('GovDelivery handler: Question ID %qid not found or has no name; skipping.', ['%qid' => $question_id]);
        continue;
      }
      
      $raw_value = $data[$element_key];

      // If this is a select-style GovDelivery question, normalize values to answer texts.
      $is_select = FALSE;
      $id_to_text = [];
      $text_lookup = [];
      if (is_array($question_def)) {
        $rt = strtolower((string) ($question_def['response_type'] ?? ''));
        $is_select = str_starts_with($rt, 'select_') || in_array($rt, ['select_one', 'select_multi'], TRUE);
        if (!empty($question_def['answers']) && is_array($question_def['answers'])) {
          foreach ($question_def['answers'] as $ans) {
            $aid = (string) ($ans['id'] ?? '');
            $atext = (string) ($ans['text'] ?? '');
            if ($aid !== '' && $atext !== '') {
              $id_to_text[$aid] = $atext;
              $text_lookup[strtolower($atext)] = $atext;
            }
          }
        }
      }

      // Expand into a list of values.
      $vals = [];
      if (is_array($raw_value)) {
        $vals = array_values(array_filter(array_map('strval', $raw_value), static fn($v) => trim($v) !== ''));
      }
      else {
        $s = trim((string) $raw_value);
        if ($s !== '') {
          // Split common delimiters
          $vals = preg_split('/[\r\n,|;]+/', $s);
          $vals = array_values(array_filter(array_map('trim', $vals), static fn($v) => $v !== ''));
        }
      }

      if (empty($vals)) {
        continue;
      }

      if ($is_select) {
        // Map codes/ids to display texts when possible so client can resolve answer-id.
        $mapped = [];
        foreach ($vals as $v) {
          $vl = strtolower($v);
          if (isset($text_lookup[$vl])) {
            $mapped[] = $text_lookup[$vl];
          }
          elseif (isset($id_to_text[$v])) {
            $mapped[] = $id_to_text[$v];
          }
          else {
            // Fallback: keep as-is; client may still send with nil answer-id.
            $mapped[] = $v;
          }
        }
        // De-duplicate while preserving order.
        $seen = [];
        $norm = [];
        foreach ($mapped as $m) {
          if (!isset($seen[$m])) { $seen[$m] = TRUE; $norm[] = $m; }
        }
        // For multi-values, pass as array; for single, pass as string.
        $answers[$question_name] = (count($norm) === 1) ? $norm[0] : $norm;
      }
      else {
        // Non-select questions: join arrays back to a string.
        $answers[$question_name] = (count($vals) === 1) ? $vals[0] : implode(', ', $vals);
      }
    }

    try {
      /** @var \Drupal\portland_govdelivery\Service\GovDeliveryClient $client */
      $client = \Drupal::service('portland_govdelivery.client');
      if (!empty($answers)) {
        \Drupal::logger('portland_govdelivery')->debug('GovDelivery handler prepared answers keys: @keys', [
          '@keys' => implode(', ', array_keys($answers)),
        ]);
      } else {
        \Drupal::logger('portland_govdelivery')->debug('GovDelivery handler: No non-empty answers prepared for submission.');
      }
      $result = $client->subscribeUser($email, $topics, NULL, $answers);
      
      $log_context = [
        '%email' => $email,
        '%topics' => implode(', ', $topics),
        '%sid' => $sid,
        '%wid' => $webform_id,
      ];
      if (!empty($answers)) {
        $log_context['%answers'] = implode(', ', array_keys($answers));
        \Drupal::logger('portland_govdelivery')->info('GovDelivery handler: Subscribed %email to topics %topics with answers for questions %answers from submission %sid (webform %wid).', $log_context);
      } else {
        \Drupal::logger('portland_govdelivery')->info('GovDelivery handler: Subscribed %email to topics %topics from submission %sid (webform %wid).', $log_context);
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

  /**
   * AJAX callback to rebuild the question mappings panel.
   */
  public function ajaxRebuildQuestionMappings(array &$form, FormStateInterface $form_state) {
    // Ensure the element exists before returning.
    if (isset($form['zz_question_mappings'])) {
      return $form['zz_question_mappings'];
    }
    // Fallback: return empty response if element not built yet.
    return ['#markup' => ''];
  }
}
