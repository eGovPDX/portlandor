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
}
