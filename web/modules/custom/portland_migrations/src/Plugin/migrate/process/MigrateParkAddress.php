<?php

namespace Drupal\portland_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;

/**
 * Create a Location item. Add the Location item to the park's address and entrance field.
 *  
 * @MigrateProcessPlugin(
 *   id = "migrate_park_address"
 * )
 */
class MigrateParkAddress extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Make sure input is valid
    if( empty($value) ) return [];

    $zip = $row->getSourceProperty('Zip');
    $title = $row->getSourceProperty('Property');

    // Create the Location
    $location = Node::create([
      'type' => 'location',
      'uid' => 1,
      'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
      'title' => $title.' main entrance',
      'status' => 1,
      // 'field_summary' => $title.' main address',
      'field_address_or_entrance' => [
        'country_code' => 'US',
        'address_line1' => $value,
        'locality' => 'Portland',
        'administrative_area' => 'OR',
        'postal_code' => ( (strlen($zip) == 5) ? $zip : '' ),
      ],
    ]);
    $location->save();
    $location->status->value = 1;
    $location->moderation_state->value = 'published';
    $location->save();
    return [
      'target_id' => $location->id(),
    ];
  }
}