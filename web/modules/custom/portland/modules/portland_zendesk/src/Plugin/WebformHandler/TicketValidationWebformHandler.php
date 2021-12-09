<?php

namespace Drupal\portland_zendesk\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\Component\Utility\Html;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\portland_zendesk\Client\ZendeskClient;

/**
 * Emails a webform submission.
 *
 * @WebformHandler(
 *   id = "ticket_validation",
 *   label = @Translation("Zendesk ticket validator"),
 *   category = @Translation("Validation"),
 *   description = @Translation("Uses the Zendesk API to validate the ticket key."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class TicketValidationWebformHandler extends WebformHandlerBase {

  use StringTranslationTrait;

  const SUBMISSION_KEY_CUSTOM_FIELD_ID = 1500013095781;

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $this->validateSubmissionKey($form_state);
  }

  /**
   * Validate submission key.
   * 
   * The submission key is the UUID of the original webform submission. This value is passed to Zendesk
   * and must be present in any webform that modifies tickets through the Zendesk API.
   */
  private function validateSubmissionKey(FormStateInterface $formState) {
    $submission_key = !empty($formState->getValue('original_submission_key')) ? $formState->getValue('original_submission_key') : NULL;
    $ticket_id = !empty($formState->getValue('report_ticket_id')) ? $formState->getValue('report_ticket_id') : NULL;

    // Skip empty unique fields or arrays (aka #multiple).
    if (empty($submission_key) || is_array($submission_key)) {
      return;
    }

    // use zendesk api to get the submission key from the ticket
    // then compare the submission key to the hidden, prepopulated
    // field in the current form

    $valid = false;

    $client = new ZendeskClient();
    $ticket = null;

    // if the ticket number is invalid, the find() call will throw an error that we need to catch
    try {
      $ticket = $client->tickets()->find($ticket_id)->ticket;
    } catch (\Exception $e) {
      $formState->setErrorByName('original_submission_key', $this->t('An error occurred while trying to access the specified ticket. Please contact a site administrator.'));
      return;
    }

    $custom_fields = [];
    foreach ($ticket->custom_fields as $item) {
      $custom_fields[$item->id] = $item->value; 
    }

    $ticket_submission_key = $custom_fields[self::SUBMISSION_KEY_CUSTOM_FIELD_ID];

    if ($ticket_submission_key != $submission_key) {
      $formState->setErrorByName('original_submission_key', $this->t('The submission key is not valid. This form cannot be processed.'));
    }
  }

}

