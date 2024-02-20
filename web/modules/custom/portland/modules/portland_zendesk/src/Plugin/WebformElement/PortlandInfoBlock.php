<?php

namespace Drupal\portland_zendesk\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'portland_info_block' element.
 *
 * @WebformElement(
 *   id = "portland_info_block",
 *   default_key = "markup",
 *   label = @Translation("Portland Info Block"),
 *   description = @Translation("Provides an element to render basic HTML markup with additional options."),
 *   category = @Translation("Markup elements"),
 *   states_wrapper = TRUE,
 * )
 */
class PortlandInfoBlock extends WebformMarkupBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'wrapper_attributes' => [],
      // Markup settings.
      'markup' => '',
      // New fields
      'alert_heading' => '',
      'alert_level' => 'information',
    ] + parent::defineDefaultProperties();
  }

  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function buildText(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    if (isset($element['#markup'])) {
      $element['#markup'] = MailFormatHelper::htmlToText($element['#markup']);
    }
    return parent::buildText($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Existing markup field
    $form['markup']['markup'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('HTML markup'),
      '#description' => $this->t('Enter custom HTML into your webform.'),
    ];

    // New fields
    $form['alert_heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alert Heading'),
      '#description' => $this->t('Enter a heading to display above the content.'),
    ];

    $form['alert_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Alert Level'),
      '#options' => [
        'information' => $this->t('Information'),
        'warning' => $this->t('Warning'),
      ],
    ];

    return $form;
  }

}
