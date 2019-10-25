<?php

/**
 * @file
 * Contains relation.page.inc.
 *
 * Page callback for Relation entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Relation templates.
 *
 * Default template: relation.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_relation(array &$variables) {
  // Fetch Relation Entity Object.
  $relation = $variables['elements']['#relation'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
