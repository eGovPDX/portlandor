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
   * 
   * Some ticket-resolution webforms may be submitted and resolved at once using the resolution form. In this
   * instance there would be no submission key or ticket ID. This validation handler should only be configured 
   * to run if there is a ticket ID present.
   */
  private function validateSubmissionKey(FormStateInterface $formState) {
    $submission_key = !empty($formState->getValue('original_submission_key')) ? $formState->getValue('original_submission_key') : NULL;
    $ticket_id = !empty($formState->getValue('report_ticket_id')) ? $formState->getValue('report_ticket_id') : NULL;

    // use zendesk api to get the ticket specifed in report_ticket_id and compare the
    // submission key (uuid). This is used to ensure that bad actor can't start updating
    // Zendeks tickets through a webform by simply incrementing ticket ID numbers.
    // A validation error is thrown if either the ticket does not exist or if the 
    // submission key doesn't match.

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

    if (!$ticket) {
      $formState->setErrorByName('report_ticket_id', $this->t('An error occurred while trying to access the specified ticket. Please contact a site administrator.'));
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

