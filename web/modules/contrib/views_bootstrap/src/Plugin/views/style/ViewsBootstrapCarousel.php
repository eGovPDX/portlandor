<?php

namespace Drupal\views_bootstrap\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item as a row in a Bootstrap Carousel.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "views_bootstrap_carousel",
 *   title = @Translation("Bootstrap Carousel"),
 *   help = @Translation("Displays rows in a Bootstrap Carousel."),
 *   theme = "views_bootstrap_carousel",
 *   theme_file = "../views_bootstrap.theme.inc",
 *   display_types = {"normal"}
 * )
 */
class ViewsBootstrapCarousel extends StylePluginBase {
  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * Definition.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['interval'] = ['default' => 5000];
    $options['navigation'] = ['default' => TRUE];
    $options['indicators'] = ['default' => TRUE];
    $options['pause'] = ['default' => TRUE];
    $options['wrap'] = ['default' => TRUE];

    $options['image'] = ['default' => ''];
    $options['title'] = ['default' => ''];
    $options['description'] = ['default' => ''];

    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $fields = ['' => t('<None>')];
    $fields += $this->displayHandler->getFieldLabels(TRUE);

    $form['interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Interval'),
      '#description' => t('The amount of time to delay between automatically cycling an item. If false, carousel will not automatically cycle.'),
      '#default_value' => $this->options['interval'],
    ];

    $form['navigation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show navigation'),
      '#default_value' => $this->options['navigation'],
    ];

    $form['indicators'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show indicators'),
      '#default_value' => $this->options['indicators'],
    ];

    $form['pause'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause on hover'),
      '#description' => t('Pauses the cycling of the carousel on mouseenter and resumes the cycling of the carousel on mouseleave.'),
      '#default_value' => $this->options['pause'],
    ];

    $form['wrap'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Wrap'),
      '#description' => t('The carousel should cycle continuously or have hard stops.'),
      '#default_value' => $this->options['wrap'],
    ];

    $form['image'] = [
      '#type' => 'select',
      '#title' => $this->t('Image'),
      '#options' => $fields,
      '#default_value' => $this->options['image'],
    ];

    $form['title'] = [
      '#type' => 'select',
      '#title' => $this->t('Title'),
      '#options' => $fields,
      '#default_value' => $this->options['title'],
    ];

    $form['description'] = [
      '#type' => 'select',
      '#title' => $this->t('Description'),
      '#options' => $fields,
      '#default_value' => $this->options['description'],
    ];
  }

}
