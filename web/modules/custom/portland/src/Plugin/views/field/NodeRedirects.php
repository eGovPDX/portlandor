<?php
 
/**
 * @file
 * Definition of Drupal\portland\Plugin\views\field\NodeRedirects
 */
 
namespace Drupal\portland\Plugin\views\field;
 
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
 
/**
 * Field handler to show redirects.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_redirects")
 */
class NodeRedirects extends FieldPluginBase {
 
  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }
 
  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $values->_entity;
    $nid = $entity->Id();
    $type = $entity->getEntityTypeId();
    if ($nid && $type) {
      $redirects = \Drupal::service('redirect.repository')->findByDestinationUri(["internal:/$type/$nid", "entity:$type/$nid"]);
      if(empty($redirects)) return '';

      // Get the "redirected from path" string
      $redirectPaths = [];
      foreach($redirects as $key => $redirect) {
        $redirectPaths[] = $redirect->getSource()['path'];
      }

      // Render as text if it's CSV export
      if($this->view->current_display == 'csv') {
        return implode(PHP_EOL, $redirectPaths);
      }
      // Render as HTML if not the CSV export
      else {
        $render_array = [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          // '#title' => 'Redirects',
          '#items' => $redirectPaths,
          // '#attributes' => ['class' => 'mylist'],
          '#wrapper_attributes' => ['class' => 'container'],
        ];
        return \Drupal::service('renderer')->render($render_array); 
      }
    }
    return '';
  }
}
