<?php

namespace Drupal\ds\Plugin\DsField;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a common interface for all ds field plugins.
 */
interface DsFieldInterface extends ConfigurablePluginInterface, ContainerFactoryPluginInterface {

  /**
   * Renders a field.
   *
   * @return array
   *   A renderable array representing the content of the field.
   */
  public function build();

  /**
   * Returns the summary of the chosen settings.
   *
   * @param array $settings
   *   Contains the settings of the field.
   *
   * @return array
   *   A render array containing the summary.
   */
  public function settingsSummary($settings);

  /**
   * The form that holds the settings for this plugin.
   *
   * @param array $form
   *   The form definition array for the field configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The renderable form array representing the entire configuration form.
   */
  public function settingsForm($form, FormStateInterface $form_state);

  /**
   * Returns a list of possible formatters for this field.
   *
   * @return array
   *   A list of possible formatters.
   */
  public function formatters();

  /**
   * Returns if the field is allowed on the field UI screen.
   *
   * @return bool
   *   TRUE when field allowed, FALSE otherwise.
   */
  public function isAllowed();

  /**
   * Gets the current entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The current entity.
   */
  public function entity();

  /**
   * Gets the current entity type.
   *
   * @return string
   *   The entity type ID.
   */
  public function getEntityTypeId();

  /**
   * Gets the current bundle.
   *
   * @return string
   *   The configured bundle of the entity.
   */
  public function bundle();

  /**
   * Gets the view mode.
   *
   * @return string
   *   The configured view mode.
   */
  public function viewMode();

  /**
   * Gets the field configuration.
   *
   * @return array
   *   The configured field settings.
   */
  public function getFieldConfiguration();

  /**
   * Gets the field name.
   *
   * @return string
   *   The field name.
   */
  public function getName();

  /**
   * Returns the title of the field.
   *
   * @return string
   *   The configured field title.
   */
  public function getTitle();

  /**
   * Defines if we are dealing with a multivalue field.
   *
   * @return bool
   *   TRUE when field has multiple values, FALSE otherwise.
   */
  public function isMultiple();

}
