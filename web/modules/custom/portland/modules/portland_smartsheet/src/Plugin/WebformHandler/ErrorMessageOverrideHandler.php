<?php

namespace Drupal\portland_smartsheet\Plugin\WebformHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionForm;

/**
 * @WebformHandler(
 *   id = "portland_error_message_override_handler",
 *   label = @Translation("Portland: Error message override"),
 *   category = @Translation("Validation"),
 *   description = @Translation("Allows for providing a custom error message when an element has an error"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class ErrorMessageOverrideHandler extends WebformHandlerBase {
  /**
   * Provides list of setting fields
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'element_id' => '',
      'error_message' => 'This selection is invalid.',
    ];
  }

  /**
   * @return array
   */
  public function defaultConfigurationNames() {
    return array_keys($this->defaultConfiguration());
  }

  public function validateElementId($element, FormStateInterface $form_state) {
    $target_element_id = $form_state->getValue($element['#parents']);
    $target_element = $this->getWebform()->getElement($target_element_id);
    if (empty($target_element)) {
      $form_state->setError($element, $this->t('This element ID does not exist.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['element_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Element ID'),
      '#description' => $this->t('ID of the element to validate'),
      '#description_display' => 'before',
      '#default_value' => $this->configuration['element_id'],
      '#required' => true,
      '#element_validate' => [[$this, 'validateElementId']],
    ];

    $form['error_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Error message'),
      '#description' => $this->t('Error message to use when the selection isn\'t valid'),
      '#description_display' => 'before',
      '#default_value' => $this->configuration['error_message'],
      '#required' => true,
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * Saves handler settings to config
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getUserInput()['settings'];
    $this->configuration['element_id'] = $values['element_id'];
    $this->configuration['error_message'] = $values['error_message'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $element_id = $this->configuration['element_id'];

    // If the element has an existing error, let's override the message
    if (array_key_exists($element_id, $form_state->getErrors())) {
      // Form state doesn't allow us to override errors, so we have to clear them first
      $errors = $form_state->getErrors();
      $errors[$element_id] = $this->t($this->configuration['error_message']);
      $form_state->clearErrors();
      foreach ($errors as $name => $error) {
        $form_state->setErrorByName($name, $error);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#markup' => '<strong>' . htmlentities($this->configuration['element_id']) . '</strong>: '
        . htmlentities($this->configuration['error_message']),
    ];
  }
}
