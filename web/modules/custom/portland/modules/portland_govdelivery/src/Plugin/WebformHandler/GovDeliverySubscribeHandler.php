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
      'topics' => [],
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

    $form['topics'] = [
      '#type' => 'select',
      '#title' => $this->t('Topics'),
      '#description' => $this->t('Select the GovDelivery topic(s) to subscribe the user to.'),
      '#options' => $options,
      '#default_value' => $config['topics'] ?? [],
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['use-select2'],
      ],
    ];
    // Attach Select2 if available.
    $form['#attached']['library'][] = 'select2/select2';

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
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['email_element'] = trim((string) $form_state->getValue('email_element'));
    $this->configuration['topics'] = (array) $form_state->getValue('topics');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $data = $webform_submission->getData();
    $email_element = (string) ($this->configuration['email_element'] ?? '');
    $topics = (array) ($this->configuration['topics'] ?? []);

    $sid = $webform_submission->id();
    $webform_id = $webform_submission->getWebform()->id();

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
      // Use current interface language as locale, if desired in future. For MVP, omit locale.
      $result = $client->subscribeUser($email, $topics, NULL);
      
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
}
