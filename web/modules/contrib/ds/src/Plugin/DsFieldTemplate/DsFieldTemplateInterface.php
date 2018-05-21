<?php

namespace Drupal\ds\Plugin\DsFieldTemplate;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a common interface for all ds field template plugins.
 */
interface DsFieldTemplateInterface {

  /**
   * Lets you add you add additional form element for your layout.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   */
  public function alterForm(&$form);

  /**
   * Gets the entity this layout belongs too.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  public function getEntity();

  /**
   * Sets the entity this layout belong too.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  public function setEntity(EntityInterface $entity);

  /**
   * Massages the values before they get rendered.
   *
   * @param array $field_settings
   *   The ds field settings.
   * @param array $values
   *   The values.
   */
  public function massageRenderValues(&$field_settings, $values);

  /**
   * Gets the chosen theme function.
   *
   * @return string
   *   The theme function.
   */
  public function getThemeFunction();

  /**
   * Creates default configuration for the layout.
   *
   * @return array
   *   Keyed array of default settings.
   */
  public function defaultConfiguration();

  /**
   * Get the selected configuration.
   *
   * @return array
   *   Keyed array of configuration values.
   */
  public function getConfiguration();

  /**
   * Set the configuration for this layout.
   *
   * @param array $configuration
   *   Keyed array of new configuration values.
   */
  public function setConfiguration(array $configuration);

}
