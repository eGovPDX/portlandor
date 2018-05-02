<?php

/**
 * @file
 * Contains \Drupal\prepopulate\Controller.
 */

namespace Drupal\prepopulate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Element;
use Drupal;


class prepopulateController extends ControllerBase {
  public function __construct() {
    $this->database = Drupal::database();
  }
  /**
    * Internal helper to set element values from the $_REQUEST variable.
    *
    * @param array &$form
    *   A form element.
    * @param mixed &$request_slice
    *   String or array. Value(s) to be applied to the element.
   */
  function _prepopulate_request_walk(&$form, &$request_slice) {
    $limited_types = array(
      'actions',
      'button',
      'container',
      'hidden',
      'image_button',
      'markup',
      'password',
      'password_confirm',
      'text_format',
      'token',
      'value',
    );
    if (is_array($request_slice)) {
      foreach (array_keys($request_slice) as $request_variable) {
        if (isset($form[$request_variable])) {
          if (isset($form[$request_variable]['widget'][0]['value']['#type'])) {
            $type = $form[$request_variable]['widget'][0]['value']['#type'];
          }
          elseif (isset($form[$request_variable]['widget'][0]['target_id']['#type'])) {
            $type = $form[$request_variable]['widget'][0]['target_id']['#type'];
          }
          elseif (isset($form[$request_variable]['widget']['#type'])) {
            $type = $form[$request_variable]['widget']['#type'];
          }
          
          if (Element::child($request_variable) && !empty($form[$request_variable]) && (!isset($type) || !in_array($type, $limited_types))) {
            if (!isset($form[$request_variable]['#access']) || $form[$request_variable]['#access'] != FALSE) {
              $this->_prepopulate_request_walk($form[$request_variable], $request_slice[$request_variable]);
            }
          }
        }
      }
    }
    else {
      $widget = $form['widget'];
      if (empty($widget) || (isset($widget[0]['value']['#type']) && $widget[0]['value']['#type'] == 'markup')) {
        $form['widget'][0]['value']['#value'] = check_plain($request_slice);
      }
      else {
        if (isset($form['widget'][0]['value'])) {
          $form['widget'][0]['value']['#value'] = $request_slice;
        }
        else {
          if (isset($form['widget'][0]['target_id'])) {
            // Referenced element: look for name ? Title (id)
            if (is_numeric($request_slice) && ($target_node = \Drupal\node\Entity\Node::load($request_slice)) && $target_node->access('view')) {
              $form['widget'][0]['target_id']['#value'] = $target_node->getTitle() . ' (' . $request_slice . ')';
            }
            else {
              $form['widget'][0]['target_id']['#value'] = $request_slice;
            }
          }
        }
      }
      $type = (isset($widget['#type'])) ? $widget['#type'] : NULL;
      if ($type == 'select') {
        $form['widget']['#value'] = $request_slice;
      }
    }
  }
}
