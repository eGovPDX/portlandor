<?php

use Drupal\Core\Url;

function template_preprocess_views_portland_alerts(array &$variables) {
  $variables['element'] = [];

  $variables['element']['#attached']['library'][] = 'portland/portland-alerts';

  foreach ($variables['rows'] as $delta => $row) {
    $variables['element'][$delta]['#theme'] = 'portland_alert';
    $variables['element'][$delta]['#dismissible'] = $variables['options']['dismissible'];
    $variables['element'][$delta]['#title'] = $variables['view']->style_plugin->getField($delta, $variables['options']['mapping']['title_field']);
    $variables['element'][$delta]['#type'] = $variables['view']->style_plugin->getFieldValue($delta, $variables['options']['mapping']['type_field']);
    $variables['element'][$delta]['#text'] = $variables['view']->style_plugin->getField($delta, $variables['options']['mapping']['summary_field']);
    $variables['element'][$delta]['#changed'] = $variables['view']->style_plugin->getField($delta, $variables['options']['mapping']['changed_field']);

    $nid = $variables['view']->style_plugin->getFieldValue($delta, $variables['options']['mapping']['id_field']);
    $variables['element'][$delta]['#nid'] = $nid;

    $changed = $variables['view']->style_plugin->getFieldValue($delta, $variables['options']['mapping']['changed_field']);
    $variables['element'][$delta]['#changed_timestamp'] = $changed;
    $variables['element']['#attached']['drupalSettings']['portland_alert'][$nid]['changed'] = $changed;
  }

}
