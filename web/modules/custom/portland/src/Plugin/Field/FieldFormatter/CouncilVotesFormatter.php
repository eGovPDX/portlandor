<?php

namespace Drupal\portland\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'council_votes' formatter.
 *
 * @FieldFormatter(
 *   id = "council_votes",
 *   label = @Translation("Council Votes"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class CouncilVotesFormatter extends FormatterBase {
  private const VOTE_TYPE_SORT_ORDER = [
    'Aye' => [],
    'Nay' => [],
    'Absent' => [],
    'Abstain' => [],
  ];

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Shows Council Votes grouped by type (Yea, Nay, etc.)');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // group the vote entities by vote type and extract the council member's group entity
    $grouped_votes = [];
    foreach ($items->referencedEntities() as $entity) {
      $vote_type = $entity->field_voted_as_follows[0]->value;
      // KLUDGE: City Charter uses the word "Aye" but all of our existing votes are hard-coded to "Yea" since it's an open text field
      // if the vote type is Yea, replace with the correct wording
      if ($vote_type === "Yea") $vote_type = "Aye";

      $grouped_votes[$vote_type][] = $entity->field_council_member->referencedEntities()[0] ?? null;
    }

    // sort the vote types according to the order defined in the const
    $grouped_votes = array_merge(self::VOTE_TYPE_SORT_ORDER, $grouped_votes);

    $element[0] = [
      '#type' => 'html_tag',
      '#tag' => 'ul',
      '#attributes' => [
        'class' => 'list-unstyled',
      ],
    ];
    foreach ($grouped_votes as $vote_type => $voters) {
      $vote_count = count($voters);
      if ($vote_count === 0) continue;

      // create an array of <li> elements for each council member's last name
      $voter_list = array_map(
        fn ($m) =>
          $m->field_name[0]->value
            ? '<li>' . end(explode(' ', $m->field_name[0]->value)) . '</li>'
            : '',
        $voters);
      // create an accessible list tree of voters
      $element[0][$vote_type] = [
        '#type' => 'html_tag',
        '#tag' => 'li',
        'label' => [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'aria-label' => $this->t('@amt council members voted @type', [ '@amt' => $vote_count, '@type' => $vote_type ]),
          ],
          '#value' => "$vote_type ($vote_count): ",
        ],
        'list' => [
          '#type' => 'html_tag',
          '#tag' => 'ul',
          '#attributes' => [
            'class' => 'list-inline-comma',
          ],
          '#value' => implode('', $voter_list),
        ],
      ];
    }

    return $element;
  }
}
