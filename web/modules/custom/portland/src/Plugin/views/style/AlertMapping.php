<?php

namespace Drupal\portland\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\Mapping;

/**
 * Provides a plugin for to map fields to the alert pattern.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "portland_alerts",
 *   title = @Translation("Portland alerts"),
 *   help = @Translation("Maps specific fields to alert pattern fields."),
 *   theme = "views_portland_alerts",
 *   theme_file = "../portland.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class AlertMapping extends Mapping {
  /**
   * {@inheritdoc}
   */
  protected function defineMapping() {
    return [
      'title_field' => [
        '#title' => $this->t('Title field'),
        '#description' => $this->t('Choose the field with the custom title.'),
        '#toggle' => TRUE,
        '#required' => TRUE,
      ],
      'id_field' => [
        '#title' => $this->t('ID field'),
        '#description' => $this->t('Choose the field with the ID for the alert.'),
        '#toggle' => TRUE,
        '#required' => TRUE,
      ],
      'changed_field' => [
        '#title' => $this->t('Changed timestamp field'),
        '#description' => $this->t('Choose the field with the changed timestamp.'),
        '#toggle' => TRUE,
        '#required' => TRUE,
      ],
      'type_field' => [
        '#title' => $this->t('Type field'),
        '#description' => $this->t('Choose the field where the type should be derived from.'),
        '#required' => TRUE,
      ],
      'summary_field' => [
        '#title' => $this->t('Summary field'),
        '#description' => $this->t('Choose the field with the custom summary.'),
        '#required' => TRUE,
      ],
      'action_link_field' => [
        '#title' => $this->t('Action link field'),
        '#description' => $this->t('Choose the field with the action link.'),
      ],

    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['dismissible'] = TRUE;

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['dismissible'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Dismissible'),
      '#description' => $this->t('Select whether these alerts should be dismissible.'),
      '#default_value' => $this->options['dismissible'],
    ];
  }
}
