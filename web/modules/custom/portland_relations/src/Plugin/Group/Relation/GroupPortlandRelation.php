<?php

namespace Drupal\portland_relations\Plugin\Group\Relation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group\Plugin\Annotation\GroupRelationType;
use Drupal\group\Plugin\Group\Relation\GroupRelationBase;

/**
 * Provides a group relation type for nodes.
 */
#[GroupRelationType(
  id: 'group_custom_entity',
  entity_type_id: 'custom_entity',
  label: new TranslatableMarkup('Group custom entity'),
  description: new TranslatableMarkup('Adds custom entity to groups both publicly and privately.'),
  reference_label: new TranslatableMarkup('Title'),
  reference_description: new TranslatableMarkup('The title of the custom entity to add to the group'),
  entity_access: TRUE,
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
    return $dependencies;
  }
}
