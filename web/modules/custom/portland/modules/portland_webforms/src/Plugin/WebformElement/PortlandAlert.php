<?php

namespace Drupal\portland_webforms\Plugin\WebformElement;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'portland_alert' element.
 *
 * @WebformElement(
 *   id = "portland_alert",
 *   label = @Translation("Portland Alert"),
 *   description = @Translation("Displays a styled alert box with configurable variant and classes."),
 *   category = @Translation("Portland elements")
 * )
 *
 * @see \Drupal\webform\Plugin\WebformElementBase
 */
class PortlandAlert extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties(): array {
    return [
      'alert_variant' => 'info',
      'alert_message' => '',
    ] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $element = $form_state->get('element');

    $form['alert_variant'] = [
      '#type' => 'select',
      '#title' => $this->t('Alert variant'),
      '#options' => [
        'info' => $this->t('Info'),
        'warning' => $this->t('Warning'),
        'success' => $this->t('Success'),
      ],
      '#default_value' => $element['#alert_variant'] ?? 'info',
      '#required' => TRUE,
      '#weight' => -99,
    ];

    $form['alert_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#default_value' => $element['#alert_message'] ?? '',
      '#required' => TRUE,
      '#description' => $this->t('Main alert message text. Line breaks are preserved.'),
      '#weight' => -98,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    $info = parent::getInfo();
    $info['#input'] = FALSE;
    $info['#theme_wrappers'] = ['webform_element'];
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, ?WebformSubmissionInterface $webform_submission = NULL): void {
    parent::prepare($element, $webform_submission);

    $allowed_variants = ['info', 'warning', 'success'];
    $variant = $element['#alert_variant'] ?? 'info';
    if (!in_array($variant, $allowed_variants, TRUE)) {
      $variant = 'info';
    }

    $classes = ['alert', 'alert--' . $variant, 'form-alert', 'next-steps'];
    $heading = trim((string) ($element['#title'] ?? ''));
    $message = trim((string) ($element['#alert_message'] ?? ''));

    $heading_markup = $heading !== ''
      ? '<h2>' . Html::escape($heading) . '</h2>'
      : '';

    $message_markup = $message !== ''
      ? '<p class="mb-0">' . nl2br(Html::escape($message), FALSE) . '</p>'
      : '';

    $element['content'] = [
      '#type' => 'markup',
      '#markup' => '<div class="' . implode(' ', $classes) . '">' . $heading_markup . $message_markup . '</div>',
    ];
  }

}
