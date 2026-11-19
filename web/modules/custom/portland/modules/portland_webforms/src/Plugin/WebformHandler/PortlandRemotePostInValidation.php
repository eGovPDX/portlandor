<?php

namespace Drupal\portland_webforms\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformMessageManagerInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Plugin\WebformHandler\RemotePostWebformHandler;


/**
 * @WebformHandler(
 *   id = "portland_remote_post_in_validation",
 *   label = @Translation("Remote post in validation phase"),
 *   category = @Translation("External"),
 *   description = @Translation("Extends the webform remote post handler and executes the Completed state in the validation phase instead of after submission so that errors stop the form submission."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 */
class PortlandRemotePostInValidation extends RemotePostWebformHandler {
  private FormStateInterface $form_state;

  /**
   * Unset the states that should not be used.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form[WebformSubmissionInterface::STATE_UPDATED]['#access'] = FALSE;
    $form[WebformSubmissionInterface::STATE_DELETED]['#access'] = FALSE;
    $form[WebformSubmissionInterface::STATE_DRAFT_CREATED]['#access'] = FALSE;
    $form[WebformSubmissionInterface::STATE_DRAFT_UPDATED]['#access'] = FALSE;
    $form[WebformSubmissionInterface::STATE_CONVERTED]['#access'] = FALSE;

    return $form;
  }

  /**
   * Override postSave to be a no-op so that the remote post is done in the validation phase instead of after submission.
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {

  }

  /**
   * Override the handleError method to add a validation error to the form state so that the submission is stopped.
   */
  protected function handleError($state, $message, $request_url, $request_method, $request_type, $request_options, $response) {
    // If debugging is enabled, display the error message on screen.
    $this->debug($message, $state, $request_url, $request_method, $request_type, $request_options, $response, 'error');

    // Log error message.
    $context = [
      '@form' => $this->getWebform()->label(),
      '@state' => $state,
      '@type' => $request_type,
      '@url' => $request_url,
      '@message' => $message,
      'webform_submission' => $this->getWebformSubmission(),
      'handler_id' => $this->getHandlerId(),
      'operation' => 'error',
      'link' => $this->getWebform()
        ->toLink($this->t('Edit'), 'handlers')
        ->toString(),
    ];

    $this->getLogger('webform_submission')
      ->error('@form webform remote @type post (@state) to @url failed. @message', $context);

    // Display error to user.
    $this->messageManager->display(WebformMessageManagerInterface::SUBMISSION_EXCEPTION_MESSAGE, 'error');
    // Add validation error to prevent submission.
    $this->form_state->setErrorByName('');
  }

  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // By posting during the validate phase, we can interrupt the form submission if any errors happen.
    // But we need to check that the validation was triggered by the submit button, and that there were also no validation errors.
    if ($form_state->hasAnyErrors() || !$form_state->getTriggeringElement() || $form_state->getTriggeringElement()['#parents'][0] !== "submit") return;

    $this->messageManager->setWebformSubmission($webform_submission);
    // Save the form state so that we can add validation errors to it in the handleError method.
    $this->form_state = $form_state;
    $this->remotePost(WebformSubmissionInterface::STATE_COMPLETED, $webform_submission);
  }
}
