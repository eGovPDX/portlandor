<?php

namespace Drupal\portland_relations\Plugin\Group\Relation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group\Plugin\Attribute\GroupRelationType;
use Drupal\group\Plugin\Group\Relation\GroupRelationBase;

/**
 * Provides a group relation type for nodes.
 */
#[GroupRelationType(
  id: 'group_portland_relation',
  entity_type_id: 'relation',
  label: new TranslatableMarkup('Group Portland Relation'),
  description: new TranslatableMarkup('Adds Portland Relation entity type to groups both publicly and privately.'),
  reference_label: new TranslatableMarkup('Title'),
  reference_description: new TranslatableMarkup('The title of the relation type to add to the group'),
  entity_access: TRUE,
  deriver: 'Drupal\portland_relations\Plugin\Group\Relation\GroupPortlandRelationDeriver',
)]
class GroupPortlandRelation extends GroupRelationBase {
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['entity_cardinality'] = 1;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Disable the entity cardinality field as the functionality of this module
    // relies on a cardinality of 1. We don't just hide it, though, to keep a UI
    // that's consistent with other group relations.
    $info = $this->t("This field has been disabled by the plugin to guarantee the functionality that's expected of it.");
    $form['entity_cardinality']['#disabled'] = TRUE;
    $form['entity_cardinality']['#description'] .= '<br /><em>' . $info . '</em>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $dependencies['config'][] = 'portland_relations.relation_type.' . $this->getRelationType()->getEntityBundle();
    return $dependencies;
  }
}
