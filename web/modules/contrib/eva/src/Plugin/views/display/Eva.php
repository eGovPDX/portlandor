<?php

namespace Drupal\eva\Plugin\views\display;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;

/**
 * The plugin that handles an EVA display in views.
 *
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "entity_view",
 *   title = @Translation("EVA"),
 *   admin = @Translation("EVA"),
 *   help = @Translation("Attach a view to an entity"),
 *   theme = "eva_display_entity_view",
 *   uses_menu_links = FALSE,
 *   uses_hook_entity_view = TRUE,
 * )
 */

class Eva extends DisplayPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\display\PathPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['entity_type']['default'] = '';
    $options['bundles']['default'] = array();
    $options['argument_mode']['default'] = 'id';
    $options['default_argument']['default'] = '';


    $options['title']['default'] = '';
    $options['defaults']['default']['title'] = FALSE;

    return $options;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::optionsSummary().
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    $categories['entity_view'] = array(
      'title' => $this->t('Entity content settings'),
      'column' => 'second',
      'build' => array(
        '#weight' => -10,
      ),
    );

    if ($entity_type = $this->getOption('entity_type')) {
      $entity_info = \Drupal::entityManager()->getDefinition($entity_type);
      $type_name = $entity_info->get('label');

      $bundle_names = array();
      $bundle_info = \Drupal::entityManager()->getBundleInfo($entity_type);
      foreach ($this->getOption('bundles') as $bundle) {
        $bundle_names[] = $bundle_info[$bundle]['label'];
      }
    }

    $options['entity_type'] = array(
      'category' => 'entity_view',
      'title' => $this->t('Entity type'),
      'value' => empty($type_name) ? $this->t('None') : $type_name,
    );

    $options['bundles'] = array(
      'category' => 'entity_view',
      'title' => $this->t('Bundles'),
      'value' => empty($bundle_names) ? $this->t('All') : implode(', ', $bundle_names),
    );

    $argument_mode = $this->getOption('argument_mode');
    $options['arguments'] = array(
      'category' => 'entity_view',
      'title' => $this->t('Arguments'),
      'value' => empty($argument_mode) ? $this->t('None') : SafeMarkup::checkPlain($argument_mode),
    );

    $options['show_title'] = array(
      'category' => 'entity_view',
      'title' => $this->t('Show title'),
      'value' => $this->getOption('show_title') ? $this->t('Yes') : $this->t('No'),
    );
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\callbackPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $entity_info = \Drupal::entityManager()->getDefinitions();
    $entity_type = $this->getOption('entity_type');

    switch ($form_state->get('section')) {
      case 'entity_type':
        foreach ($entity_info as $type => $info) {
          // is this a content/front-facing entity?
          if ($info instanceof \Drupal\Core\Entity\ContentEntityType) {
            $entity_names[$type] = $info->get('label');
          }
        }

        $form['#title'] .= $this->t('Entity type');
        $form['entity_type'] = array(
          '#type' => 'radios',
          '#required' => TRUE,
          '#validated' => TRUE,
          '#title' => $this->t('Attach this display to the following entity type'),
          '#options' => $entity_names,
          '#default_value' => $this->getOption('entity_type'),
        );
        break;

      case 'bundles':
        $options = array();
        foreach (\Drupal::entityManager()->getBundleInfo($entity_type) as $bundle => $info) {
          $options[$bundle] = $info['label'];
        }
        $form['#title'] .= $this->t('Bundles');
        $form['bundles'] = array(
          '#type' => 'checkboxes',
          '#title' => $this->t('Attach this display to the following bundles.  If no bundles are selected, the display will be attached to all.'),
          '#options' => $options,
          '#default_value' => $this->getOption('bundles'),
        );
        break;

      case 'arguments':
        $form['#title'] .= $this->t('Arguments');
        $default = $this->getOption('argument_mode');
        $options = array(
          'None' => $this->t("No special handling"),
          'id' => $this->t("Use the ID of the entity the view is attached to"),
          'token' => $this->t("Use tokens from the entity the view is attached to"),
        );

        $form['argument_mode'] = array(
          '#type' => 'radios',
          '#title' => $this->t("How should this display populate the view's arguments?"),
          '#options' => $options,
          '#default_value' => $default,
        );

        $form['token'] = array(
          '#type' => 'fieldset',
          '#title' => $this->t('Token replacement'),
          '#collapsible' => TRUE,
          '#states' => array(
            'visible' => array(
              ':input[name=argument_mode]' => array('value' => 'token'),
            ),
          ),
        );

        $form['token']['default_argument'] = array(
          '#title' => $this->t('Arguments'),
          '#type' => 'textfield',
          '#default_value' => $this->getOption('default_argument'),
          '#description' => $this->t('You may use token replacement to provide arguments based on the current entity. Separate arguments with "/".'),
        );
        break;

      case 'show_title':
        $form['#title'] .= $this->t('Show title');
        $form['show_title'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Show the title of the view above the attached view.'),
          '#default_value' => $this->getOption('show_title'),
        );
        break;
    }
  }

  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);
    switch ($form_state->get('section')) {
      case 'entity_type':
        if (empty($form_state->getValue('entity_type'))) {
          $form_state->setError($form['entity_type'], $this->t('Must select an entity'));
        }
        break;
    }
  }

  public function validate() {
    $errors = array();
    if (empty($this->getOption('entity_type'))) {
      $errors[] = $this->t('Display "@display" must be attached to an entity.', array('@display' => $this->display['display_title']));
    }
    return $errors;
  }

  public function remove() {
    // clean up display configs before the display disappears
    $longname = $this->view->storage->get('id') . '_' . $this->display['id'];
    _eva_clear_detached($longname);

    parent::remove();
  }

  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'entity_type':
        $new_entity = $form_state->getValue('entity_type');
        $old_entity = $this->getOption('entity_type');
        $this->setOption('entity_type', $new_entity);

        if ($new_entity != $old_entity) {
          // Each entity has its own list of bundles and view modes. If there's
          // only one on the new type, we can select it automatically. Otherwise
          // we need to wipe the options and start over.
          $new_entity_info = \Drupal::entityManager()->getDefinition($new_entity);
          $new_bundles_keys = \Drupal::entityManager()->getBundleInfo($new_entity);
          $new_bundles = array();
          if (count($new_bundles_keys) == 1) {
            $new_bundles[] = $new_bundles_keys[0];
          }
          $this->setOption('bundles', $new_bundles);
        }
        break;
      case 'bundles':
        $this->setOption('bundles', array_values(array_filter($form_state->getValue('bundles'))));
        break;
      case 'arguments':
        $this->setOption('argument_mode', $form_state->getValue('argument_mode'));
        if ($form_state->getValue('argument_mode') == 'token') {
          $this->setOption('default_argument', $form_state->getValue('default_argument'));
        }
        else {
          $this->setOption('default_argument', NULL);
        }
        break;
      case 'show_title':
        $this->setOption('show_title', $form_state->getValue('show_title'));
        break;
    }
  }

  public function getPath() {
    if (isset($this->view->current_entity)) {
      /** @var \Drupal\Core\Entity\EntityInterface $current_entity */
      $current_entity = $this->view->current_entity;

      /** @var \Drupal\Core\Url $uri */
      $uri = $current_entity->toUrl();
      if ($uri) {
        $uri->setAbsolute(TRUE);
        return $uri->toUriString();
      }
    }

    return parent::getPath();
  }

  function execute() {
    // Prior to this being called, the $view should already be set to this
    // display, and arguments should be set on the view.
    if (!isset($this->view->override_path)) {
      $this->view->override_path = \Drupal::service('path.current')->getPath();
    }

    $element = $this->view->render();
    if (!empty($this->view->result) || $this->getOption('empty') || !empty($this->view->style_plugin->definition['even empty'])) {
      return $element;
    }

    return [];
  }
}
