<?php

namespace Drupal\portland_webforms\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\WebformMarkup;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Plugin\WebformElementDisplayOnInterface;

/**
 * Provides a 'portland_alert' webform element.
 *
 * @WebformElement(
 *   id = "portland_alert",
 *   default_key = "alert",
 *   label = @Translation("Portland Alert"),
 *   description = @Translation("Displays a styled alert with optional title and HTML markup."),
 *   category = @Translation("Portland elements"),
 *   states_wrapper = TRUE
 * )
 */
class PortlandAlert extends WebformMarkup {

	/**
	 * Normalizes HTML editor values to a plain markup string.
	 *
	 * The webform HTML editor can submit nested value arrays depending on the
	 * configured format widget. This method safely extracts the underlying text.
	 *
	 * @param mixed $value
	 *   A potential string or nested editor value array.
	 *
	 * @return string
	 *   The editor markup as a plain string.
	 */
	protected function normalizeHtmlEditorValue($value): string {
		if (is_string($value)) {
			return $value;
		}

		if (is_array($value)) {
			if (array_key_exists('value', $value)) {
				return $this->normalizeHtmlEditorValue($value['value']);
			}

			return '';
		}

		return is_scalar($value) ? (string) $value : '';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function defineDefaultProperties() {
		$properties = parent::defineDefaultProperties();
		$properties['display_on'] = WebformElementDisplayOnInterface::DISPLAY_ON_FORM;
		return [
			'alert_markup' => '',
			'alert_variation' => 'info',
		] + $properties;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function defineTranslatableProperties() {
		return array_merge(parent::defineTranslatableProperties(), ['alert_markup']);
	}

	/**
	 * {@inheritdoc}
	 */
	public function prepare(array &$element, ?WebformSubmissionInterface $webform_submission = NULL) {
		parent::prepare($element, $webform_submission);

		$variation = in_array($element['#alert_variation'] ?? '', ['info', 'warning', 'success'], TRUE)
			? $element['#alert_variation']
			: 'info';

		$body_markup = $this->normalizeHtmlEditorValue($element['#alert_markup'] ?? '');

		$element['#markup'] = '<div class="alert alert--' . $variation . ' next-steps form-alert">' . $body_markup . '</div>';
	}

	/**
	 * {@inheritdoc}
	 */
	public function preview() {
		return parent::preview() + [
			'#alert_variation' => 'info',
			'#alert_markup' => '<p>' . $this->t('This is preview text for the Portland Alert element.') . '</p>',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function form(array $form, FormStateInterface $form_state) {
		$form = parent::form($form, $form_state);

		$form['markup']['#title'] = $this->t('Alert settings');
		unset($form['markup']['markup'], $form['markup']['display_on']);

		$form['markup']['alert_variation'] = [
			'#type' => 'select',
			'#title' => $this->t('Variation'),
			'#options' => [
				'info' => $this->t('Info'),
				'warning' => $this->t('Warning'),
				'success' => $this->t('Success'),
			],
			'#required' => TRUE,
			'#default_value' => $this->configuration['alert_variation'] ?? 'info',
		];

		$form['markup']['alert_markup'] = [
			'#type' => 'webform_html_editor',
			'#title' => $this->t('HTML markup'),
			'#description' => $this->t('Enter the body content for this alert.'),
			'#required' => TRUE,
			'#default_value' => $this->normalizeHtmlEditorValue($this->configuration['alert_markup'] ?? ''),
		];

		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
		parent::submitConfigurationForm($form, $form_state);

		$properties_value = $form_state->getValue(['properties', 'alert_markup']);
		$direct_value = $form_state->getValue('alert_markup');
		$markup_value = $properties_value ?? $direct_value;

		$form_state->setValue('alert_markup', $this->normalizeHtmlEditorValue($markup_value));
	}

}
