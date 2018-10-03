<?php

use Drupal\Core\Url;

function template_preprocess_views_portland_alerts(array &$variables) {
  $variables['element'] = [];

  $variables['element']['#attached']['library'][] = 'portland/portland-alerts';

  foreach ($variables['rows'] as $delta => $row) {
    $variables['element'][$delta]['#theme'] = 'portland_alert';
    $variables['element'][$delta]['#dismissible'] = $variables['options']['dismissible'];
    $variables['element'][$delta]['#title'] = $variables['view']->style_plugin->getField($delta, $variables['options']['mapping']['title_field']);
    $variables['element'][$delta]['#text'] = $variables['view']->style_plugin->getField($delta, $variables['options']['mapping']['summary_field']);
    $variables['element'][$delta]['#changed'] = $variables['view']->style_plugin->getField($delta, $variables['options']['mapping']['changed_field']);

    // if there is a severity field
    if($variables['options']['mapping']['severity_field'] && $variables['view']->result[$delta]) {
      try {
        $variables['element'][$delta]['#type'] = $variables['view']->style_plugin->getFieldValue($delta, $variables['options']['mapping']['severity_field']);
      } catch(Throwable $e) {
        $variables['element'][$delta]['#type'] = NULL;
      }
    }
    // if there is an icon to output
    if($variables['options']['mapping']['icon_field'] && $variables['view']->result[$delta]) {
      // get the rendered field
      try {
        $variables['element'][$delta]['#icon'] = $variables['view']->style_plugin->getField($delta, $variables['options']['mapping']['icon_field']);
      } catch(Throwable $e) {
        $variables['element'][$delta]['#icon'] = NULL;
      }
    }
    // if there is an action link
    if($variables['options']['mapping']['action_link_field'] && $variables['view']->result[$delta] && $variables['view']->field[$variables['options']['mapping']['action_link_field']]->getValue($variables['view']->result[$delta])) {
      $variables['element'][$delta]['#link_url'] = Url::fromUri($variables['view']->field[$variables['options']['mapping']['action_link_field']]->getValue($variables['view']->result[$delta], 'uri'))->toString();
      $variables['element'][$delta]['#link_text'] = $variables['view']->field[$variables['options']['mapping']['action_link_field']]->getValue($variables['view']->result[$delta], 'title');
    }

    $nid = $variables['view']->style_plugin->getFieldValue($delta, $variables['options']['mapping']['id_field']);
    $variables['element'][$delta]['#nid'] = $nid;

    $changed = $variables['view']->style_plugin->getFieldValue($delta, $variables['options']['mapping']['changed_field']);
    $variables['element'][$delta]['#changed_timestamp'] = $changed;
    $variables['element']['#attached']['drupalSettings']['portland_alert'][$nid]['changed'] = $changed;
  }

}
