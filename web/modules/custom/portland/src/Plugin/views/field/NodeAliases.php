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
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;

 
/**
 * Field handler to show aliases.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_aliases")
 */
class NodeAliases extends FieldPluginBase {
 
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
      $aliases = $this->_get_aliases_by_nid($nid);
      if(empty($aliases)) return '';

      $aliasPaths = [];
      foreach ($aliases as $alias) {
        $aliasPaths[] = $alias->alias;
      }


      // Render as text if it's CSV export
      if( strpos($this->view->current_display, 'csv') !== false) {
        return implode(PHP_EOL, $aliasPaths);
      }
      else {
        $render_array = [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          // '#title' => 'Redirects',
          '#items' => $aliasPaths,
          // '#attributes' => ['class' => 'mylist'],
          '#wrapper_attributes' => ['class' => 'container'],
        ];
  
        return \Drupal::service('renderer')->render($render_array); 
      }
    }
    return '';
  }

  /**
   * Get all aliases for a given Drupal $nid.
   */
  private function _get_aliases_by_nid($nid) {
    if(empty($nid)) return [];
    return Database::getConnection()->select('path_alias')
      ->fields('path_alias')
      ->condition('path', "/node/$nid")
      ->execute()
      ->fetchAll();
  }
}
