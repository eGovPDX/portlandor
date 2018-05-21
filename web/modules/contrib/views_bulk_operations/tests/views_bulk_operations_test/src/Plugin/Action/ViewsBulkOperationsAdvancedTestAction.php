<?php

namespace Drupal\views_bulk_operations_test\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\ViewExecutable;

/**
 * Action for test purposes only.
 *
 * @Action(
 *   id = "views_bulk_operations_advanced_test_action",
 *   label = @Translation("VBO advanced test action"),
 *   type = "",
 *   confirm = TRUE,
 *   requirements = {
 *     "_permission" = "execute advanced test action",
 *   },
 * )
 */
class ViewsBulkOperationsAdvancedTestAction extends ViewsBulkOperationsActionBase implements ViewsBulkOperationsPreconfigurationInterface, PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    // Check if $this->view is an instance of ViewsExecutable.
    if (!($this->view instanceof ViewExecutable)) {
      throw new \Exception('View passed to action object is not an instance of \Drupal\views\ViewExecutable.');
    }

    // Check if context array has been passed to the action.
    if (empty($this->context)) {
      throw new \Exception('Context array empty in action object.');
    }

    drupal_set_message(sprintf('Test action (preconfig: %s, config: %s, label: %s)',
      $this->configuration['test_preconfig'],
      $this->configuration['test_config'],
      $entity->label()
    ));

    // Unpublish entity.
    if ($this->configuration['test_config'] === 'unpublish') {
      if (!$entity->isDefaultTranslation()) {
        $entity = \Drupal::service('entity_type.manager')->getStorage('node')->load($entity->id());
      }
      $entity->setPublished(FALSE);
      $entity->save();
    }

    return 'Test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $element, array $values, FormStateInterface $form_state) {
    $element['test_preconfig'] = [
      '#title' => $this->t('Preliminary configuration'),
      '#type' => 'textfield',
      '#default_value' => isset($values['preconfig']) ? $values['preconfig'] : '',
    ];
    return $element;
  }

  /**
   * Configuration form builder.
   *
   * @param array $form
   *   Form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The configuration form.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['test_config'] = [
      '#title' => t('Config'),
      '#type' => 'textfield',
      '#default_value' => $form_state->getValue('config'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }

}
