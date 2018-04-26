<?php

namespace Drupal\views_bulk_operations\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;

/**
 * Views Bulk Operations action plugin base.
 *
 * Provides a base implementation for a configurable
 * and preconfigurable VBO Action plugin.
 */
abstract class ViewsBulkOperationsActionBase extends ActionBase implements ViewsBulkOperationsActionInterface, ConfigurablePluginInterface {

  /**
   * Action context.
   *
   * @var array
   *   Contains view data and optionally batch operation context.
   */
  protected $context;

  /**
   * The processed view.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * Configuration array.
   *
   * @var array
   */
  protected $configuration;

  /**
   * {@inheritdoc}
   */
  public function setContext(array &$context) {
    $this->context['sandbox'] = &$context['sandbox'];
    foreach ($context as $key => $item) {
      if ($key === 'sandbox') {
        continue;
      }
      $this->context[$key] = $item;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setView(ViewExecutable $view) {
    $this->view = $view;
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    $results = [];
    foreach ($objects as $entity) {
      $results[] = $this->execute($entity);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * Default configuration form validator.
   *
   * This method will be needed if a child class will implement
   * \Drupal\Core\Plugin\PluginFormInterface. Code saver.
   *
   * @param array &$form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Default configuration form submit handler.
   *
   * This method will be needed if a child class will implement
   * \Drupal\Core\Plugin\PluginFormInterface. Code saver.
   *
   * @param array &$form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    foreach ($form_state->getValues() as $key => $value) {
      $this->configuration[$key] = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
