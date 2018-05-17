<?php

namespace Drupal\bootstrap_layouts\Plugin\Layout;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Layout\LayoutDefault;

/**
 * Layout class for all bootstrap layouts.
 */
class BootstrapLayoutsBase extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Provides a default region definition.
   *
   * @return array
   *   Default region array.
   */
  protected function getRegionDefaults() {
    return [
      'wrapper' => 'div',
      'classes' => [],
      'attributes' => '',
      'add_region_classes' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration += [
      'layout' => [
        'wrapper' => 'div',
        'classes' => ['row'],
        'attributes' => '',
        'add_layout_class' => TRUE,
      ],
      'regions' => [],
    ];
    foreach ($this->getPluginDefinition()->getRegions() as $region => $info) {
      $region_configuration = [];
      foreach (['wrapper', 'classes', 'attributes'] as $key) {
        if (isset($info[$key])) {
          $region_configuration[$key] = $info[$key];
        }
      }
      $configuration['regions'][$region] = $region_configuration + $this->getRegionDefaults();
    }
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // This can potentially be invoked within a subform instead of a normal
    // form. There is an ongoing discussion around this which could result in
    // the passed form state going back to a full form state. In order to
    // prevent BC breaks, check which type of FormStateInterface has been
    // passed and act accordingly.
    // @see https://www.drupal.org/node/2868254
    // @todo Re-evaluate once https://www.drupal.org/node/2798261 makes it in.
    $complete_form_state = $form_state instanceof SubformStateInterface ? $form_state->getCompleteFormState() : $form_state;

    $configuration = $this->getConfiguration();

    /** @var \Drupal\bootstrap_layouts\BootstrapLayoutsManager $manager */
    $manager = \Drupal::getContainer()->get('plugin.manager.bootstrap_layouts');
    $classes = $manager->getClassOptions();

    $tokens = FALSE;
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $tokens = [
        '#title' => $this->t('Tokens'),
        '#type' => 'container',
      ];
      $tokens['help'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => 'all',
        '#global_types' => FALSE,
        '#dialog' => TRUE,
      ];
    }

    // Add wrappers.
    $wrapper_options = [
      'div' => 'Div',
      'span' => 'Span',
      'section' => 'Section',
      'article' => 'Article',
      'header' => 'Header',
      'footer' => 'Footer',
      'aside' => 'Aside',
      'figure' => 'Figure',
    ];

    $form['layout'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    $form['layout']['wrapper'] = [
      '#type' => 'select',
      '#title' => $this->t('Wrapper'),
      '#options' => $wrapper_options,
      '#default_value' => $complete_form_state->getValue(['layout', 'wrapper'], $configuration['layout']['wrapper']),
    ];

    $form['layout']['classes'] = [
      '#type' => 'select',
      '#title' => $this->t('Classes'),
      '#options' => $classes,
      '#default_value' => $complete_form_state->getValue(['layout', 'classes'], $configuration['layout']['classes']) ?: [],
      '#multiple' => TRUE,
    ];

    $form['layout']['add_layout_class'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add layout specific class: <code>@class</code>', ['@class' => Html::cleanCssIdentifier($this->getPluginId())]),
      '#default_value' => (int) $complete_form_state->getValue(['layout', 'add_layout_class'], $configuration['layout']['add_layout_class']),
    ];

    $form['layout']['attributes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional attributes'),
      '#description' => 'E.g. id|custom-id,role|navigation,data-something|some value',
      '#default_value' => $complete_form_state->getValue(['layout', 'attributes'], $configuration['layout']['attributes']),
    ];

    if ($tokens) {
      $form['layout']['tokens'] = $tokens;
    }

    // Add each region's settings.
    foreach ($this->getPluginDefinition()->getRegions() as $region => $region_info) {
      $region_label = $region_info['label'];
      $default_values = NestedArray::mergeDeep(
        $this->getRegionDefaults(),
        isset($configuration['regions'][$region]) ? $configuration['regions'][$region] : [],
        $complete_form_state->getValue(['regions', $region], [])
      );

      $form[$region] = [
        '#group' => 'additional_settings',
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => $this->t('Region: @region', ['@region' => $region_label]),
        '#weight' => 20,
      ];

      $form[$region]['wrapper'] = [
        '#type' => 'select',
        '#title' => $this->t('Wrapper'),
        '#options' => $wrapper_options,
        '#default_value' => $default_values['wrapper'],
      ];

      $form[$region]['classes'] = [
        '#type' => 'select',
        '#title' => $this->t('Classes'),
        '#options' => $classes,
        '#default_value' => $default_values['classes'],
        '#multiple' => TRUE,
      ];

      $form[$region]['add_region_classes'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Add region specific classes: <code>bs-region</code> and <code>bs-region--@region</code>', ['@region' => $region]),
        '#default_value' => (int) $default_values['add_region_classes'],
      ];

      $form[$region]['attributes'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Additional attributes'),
        '#description' => 'E.g. id|custom-id,role|navigation,data-something|some value',
        '#default_value' => $default_values['attributes'],
      ];

      if ($tokens) {
        $form[$region]['tokens'] = $tokens;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    // Don't use NestedArray::mergeDeep here since this will merge both the
    // default classes and the classes stored in config.
    $default = $this->defaultConfiguration();

    // Ensure top level properties exist.
    $configuration += $default;

    // Ensure specific top level sub-properties exists.
    $configuration['layout'] += $default['layout'];
    $configuration['regions'] += $default['regions'];

    // Remove any region configuration that doesn't apply to current layout.
    $regions = $this->getPluginDefinition()->getRegions();
    foreach (array_keys($configuration['regions']) as $region) {
      if (!isset($regions[$region])) {
        unset($configuration['regions'][$region]);
      }
    }

    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $defaults = $this->getRegionDefaults();
    if ($layout = $form_state->getValue('layout', $defaults)) {
      // Apply Xss::filter to attributes.
      $layout['attributes'] = Xss::filter($layout['attributes']);
      $this->configuration['layout'] = $layout;
    }

    $regions = [];
    foreach ($this->getPluginDefinition()->getRegionNames() as $name) {
      if ($region = $form_state->getValue($name, $defaults)) {
        // Apply Xss::filter to attributes.
        $region['attributes'] = Xss::filter($region['attributes']);
        $regions[$name] = $region;
      }
    }
    $this->configuration['regions'] = $regions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }
}
