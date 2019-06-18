<?php
 
/**
 * @file
 * Definition of Drupal\portland\Plugin\views\field\IsFeaturedInGroup
 */
 
namespace Drupal\portland\Plugin\views\field;
 
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
 
/**
 * TRUE if this node is featured in field_group group.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("is_featured_in_group")
 */
class IsFeaturedInGroup extends FieldPluginBase {
 
  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // $options['node_type'] = array('default' => 'article');
    return $options;
  }
 
  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // $types = NodeType::loadMultiple();
    // $options = [];
    // foreach ($types as $key => $type) {
    //   $options[$key] = $type->label();
    // }
    // $form['node_type'] = array(
    //   '#title' => $this->t('Which node type should be flagged?'),
    //   '#type' => 'select',
    //   '#default_value' => $this->options['node_type'],
    //   '#options' => $options,
    // );
 
    parent::buildOptionsForm($form, $form_state);
  }
 
  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $group = $values->_entity;
    if( $group->hasField('field_featured_content_list') ) {
      $featuredList = $group->field_featured_content_list;

      $currentNid = $values->node_field_data_group_content_field_data_nid;

      foreach( $featuredList->referencedEntities() as $featuredNode ) {
        if( $featuredNode->field_featured_content[0]->entity->id() == $currentNid ) {
          return $this->t('true');
        }
      }
    }
    return $this->t('false');
  }
}