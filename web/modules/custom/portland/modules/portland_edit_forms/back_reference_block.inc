<?php

use Drupal\Component\Utility\Xss;
use Drupal\views\Views;
use Drupal\views\Element\View;

/**
 * Implements hook_form__alter().
 *
 *
 * @param [type] $form
 * @param [type] $form_state
 * @param [type] $form_id
 * @return void
 */
function portland_edit_forms_form_node_form_alter(&$form, $form_state, $form_id)
{
  $view_id = 'back_reference_related_content';
  $display_id = 'block_1';

  if (array_key_exists('field_related_content',$form)) {
    $view = Views::getView($view_id);

    if (!$view || !$view->access($display_id)) {
      return;
    }

    $form['content_back_reference'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['message']],
    ];
    $form['content_back_reference']['title'] = [
      '#type' => 'inline_template',
      '#template' => '<p class="message__heading">{{ title|t }}</p>',
      '#context' => [
        'title' => $view->getTitle(),
      ],
    ];
    $form['content_back_reference']['view'] = View::preRenderViewElement($view->buildRenderable($display_id, [], FALSE));
    $form['content_back_reference']['#weight'] = $form['field_related_content']['#weight'];
    // group the two elements together
    $form['content_back_reference']['#group'] = $form['field_related_content']['#group'] = 'related_content';
  }
}
