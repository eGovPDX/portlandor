<?php

namespace Drupal\portland\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'council_votes' widget.
 *
 * @FieldWidget(
 *   id = "council_votes",
 *   label = @Translation("Council Votes Widget"),
 *   description = @Translation("Entity reference widget that provides a voting matrix to enter voting details"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = true
 * )
 */
class CouncilVotesWidget extends WidgetBase implements ContainerFactoryPluginInterface {
  protected EntityFieldManagerInterface $entityFieldManager;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected SelectionPluginManagerInterface $selectionManager;

  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings,
    array $third_party_settings, EntityFieldManagerInterface $entity_field_manager,
    EntityTypeManagerInterface $entity_type_manager, SelectionPluginManagerInterface $selection_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->selectionManager = $selection_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_reference_selection'),
    );
  }

  private function getExistingVotesByGid(FieldItemListInterface $items) {
    $existing_votes_by_gid = [];
    foreach ($items->referencedEntities() as $existing_entity) {
      $existing_votes_by_gid[$existing_entity->get('field_council_member')[0]->target_id] = $existing_entity;
    };

    return $existing_votes_by_gid;
  }

  /**
   * Returns array of [$gid => $elected_name_html]
   */
  private function getElectedOfficialOptions($existing_votes_by_gid) {
    $bundle_field_definitions = $this->entityFieldManager->getFieldDefinitions('relation', 'council_vote');
    $selection_handler = $this->selectionManager->getSelectionHandler($bundle_field_definitions['field_council_member']);
    $referenceable_entities = $selection_handler->getReferenceableEntities();
    $options = $referenceable_entities['elected_official'];

    // Find any existing votes with old elected officials and add them as an option,
    // so the editor can see and edit/delete them.
    $old_options = [];
    foreach ($existing_votes_by_gid as $gid => $existing_vote) {
      if (!array_key_exists($gid, $options)) {
        $elected = $existing_vote->get('field_council_member')->referencedEntities()[0] ?? null;
        if ($elected) {
          $old_options[$gid] = $elected->label();
        }
      }
    }

    return $options + $old_options;
  }

  /**
   * Returns array of [$vote_name => $vote_name]
   */
  private function getVoteOptions() {
    $bundle_field_definitions = $this->entityFieldManager->getFieldDefinitions('relation', 'council_vote');
    return options_allowed_values($bundle_field_definitions['field_voted_as_follows']->getFieldStorageDefinition()) + ['_none' => '- None -'];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $existing_votes_by_gid = $this->getExistingVotesByGid($items);
    $options_elected_official = $this->getElectedOfficialOptions($existing_votes_by_gid);
    $options_vote = $this->getVoteOptions();

    $field_name = $this->fieldDefinition->getName();
    $element = [
      '#type' => 'table',
      '#header' =>
        ['elected' => $this->t('Elected Official')] +
        $options_vote +
        ['weight' => $this->t('Weight')],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-sort-weight',
        ],
      ],
      '#element_validate' => [
        [$this, 'validateElement'],
      ],
    ];

    // sort the elected officials by the order of any existing vote entities, so we can preserve the sort order set by the user
    $existing_vote_key_order = array_flip(array_keys($existing_votes_by_gid));
    uksort($options_elected_official, fn($a, $b) => ($existing_vote_key_order[$a] ?? 99) - ($existing_vote_key_order[$b] ?? 99));

    foreach ($options_elected_official as $gid => $elected_name_html) {
      $elected_name = strip_tags($elected_name_html);
      $existing_vote = array_key_exists($gid, $existing_votes_by_gid) ? $existing_votes_by_gid[$gid]->get('field_voted_as_follows')->getString() : '_none';

      $element[$gid]['elected'] = [
        '#markup' => $elected_name,
      ];

      foreach (array_keys($options_vote) as $key) {
        $element[$gid][] = [
          '#type' => 'radio',
          '#name' => $field_name . '[target_id]' . '[' . $gid . '][vote]',
          '#value' => $existing_vote === $key ? $key : false,
          '#return_value' => $key,
        ];
      }

      $element[$gid]['#attributes']['class'][] = 'draggable';
      $element[$gid]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', [
          '@title' => $elected_name,
        ]),
        '#title_display' => 'invisible',
        '#default_value' => 0,
        // Classify the weight element for #tabledrag.
        '#attributes' => [
          'class' => [
            'table-sort-weight',
          ],
        ],
      ];
    };

    return ['target_id' => $element];


  }

  /**
   * {@inheritdoc}
   */
  public function validateElement(array $element, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $entity = $form_state->getFormObject()->getEntity();
    $items = $entity->get($field_name);

    $existing_votes_by_gid = $this->getExistingVotesByGid($items);
    $options_elected_official = $this->getElectedOfficialOptions($existing_votes_by_gid);
    $options_vote = $this->getVoteOptions();

    $updated_vote_values = $form_state->getValue([$field_name, 'target_id']);
    foreach ($updated_vote_values as $gid => $value) {
      $new_vote_value = $value['vote'];
      if (!array_key_exists($new_vote_value, $options_vote) || !array_key_exists($gid, $options_elected_official)) {
        $form_state->setError($element, t('Invalid votes entered'));
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // we only want to create/update entities if the validation stage has completed
    if (!$form_state->isValidationComplete()) return;

    $field_name = $this->fieldDefinition->getName();
    $entity = $form_state->getFormObject()->getEntity();
    $items = $entity->get($field_name);

    // we loop through each vote provided by the user,
    // update if the field contains an existing vote entity for that elected official's group ID;
    // or create a new vote entity if none exists in the field yet
    $updated_vote_values = $form_state->getValue([$field_name, 'target_id']);
    uasort($updated_vote_values, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    $existing_votes_by_gid = $this->getExistingVotesByGid($items);
    $vote_entity_ids = [];
    foreach ($updated_vote_values as $gid => $value) {
      $new_vote_value = $value['vote'];
      if (array_key_exists($gid, $existing_votes_by_gid)) {
        // case for updating existing vote entities:
        $existing_vote = $existing_votes_by_gid[$gid];
        // if new vote is set to none, delete vote entity
        if ($new_vote_value === '_none') {
          $existing_vote->delete();
        } else {
          $old_vote_value = $existing_vote->get('field_voted_as_follows')->getString();
          if ($new_vote_value !== $old_vote_value) {
            $existing_vote->set('field_voted_as_follows', $new_vote_value);
            $existing_vote->save();
          }

          $vote_entity_ids[] = $existing_vote->id();
        }
      } else if ($new_vote_value !== '_none') {
        // case for creating new vote entities
        $new_vote = $this->entityTypeManager->getStorage('relation')->create([
          'type' => 'council_vote',
          'field_council_member' => ['target_id' => $gid],
          'field_voted_as_follows' => $new_vote_value,
        ]);
        $new_vote->save();

        $vote_entity_ids[] = $new_vote->id();
      }
    }

    return array_map(fn($id) => ['target_id' => $id], $vote_entity_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return $element['target_id'];
  }
}
