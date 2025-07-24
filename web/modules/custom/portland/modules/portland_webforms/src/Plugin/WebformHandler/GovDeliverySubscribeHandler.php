<?php

namespace Drupal\portland_webforms\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Subscribes user to GovDelivery topics on form submission.
 *
 * @WebformHandler(
 *   id = "govdelivery_subscribe",
 *   label = @Translation("GovDelivery Subscribe"),
 *   category = @Translation("GovDelivery"),
 *   description = @Translation("Subscribes the user to one or more GovDelivery topics."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class GovDeliverySubscribeHandler extends WebformHandlerBase
{

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        $form['email_element_key'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Email element key'),
            '#default_value' => $this->configuration['email_element_key'] ?? '',
            '#description' => $this->t('The machine name of the element that contains the email address.'),
            '#required' => TRUE,
        ];

        $form['topic_element_key'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Topic element key'),
            '#default_value' => $this->configuration['topic_element_key'] ?? '',
            '#description' => $this->t('The machine name of the element that contains the GovDelivery topic code(s). This can be a single textfield element with a comma separated list of topic codes, or a multi-value textfield element.'),
            '#required' => TRUE,
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        parent::submitConfigurationForm($form, $form_state);

        $this->configuration['email_element_key'] = $form_state->getValue('email_element_key');
        $this->configuration['topic_element_key'] = $form_state->getValue('topic_element_key');
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission)
    {
        $data = $webform_submission->getData();
        $email_key = $this->configuration['email_element_key'] ?? '';
        $topic_key = $this->configuration['topic_element_key'] ?? '';

        $email = $data[$email_key] ?? NULL;
        $raw_topics = $data[$topic_key] ?? [];

        // Normalize topic codes to array
        if (is_string($raw_topics)) {
            // Case: comma-delimited string from single-value textfield
            $topic_codes = array_filter(array_map('trim', explode(',', $raw_topics)));
        } elseif (is_array($raw_topics)) {
            // Case: multi-value field (or checkboxes/select)
            $topic_codes = array_filter(array_map('trim', $raw_topics));
        } else {
            $topic_codes = [];
        }

        if (!$email || empty($topic_codes)) {
            \Drupal::logger('govdelivery')->warning('Missing email or topic codes. Email key: @e, Topic key: @t', [
                '@e' => $email_key,
                '@t' => $topic_key,
            ]);
            return;
        }

        try {
            \Drupal::httpClient()->request('GET', 'https://public.govdelivery.com/api/add_script_subscription', [
                'query' => [
                    'email' => $email,
                    'code' => implode(',', $topic_codes),
                ],
                'timeout' => 10,
            ]);
        } catch (\Exception $e) {
            \Drupal::logger('govdelivery')->error('GovDelivery subscription error: @message', [
                '@message' => $e->getMessage(),
            ]);
        }
    }
}
