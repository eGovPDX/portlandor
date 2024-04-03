<?php

namespace Drupal\portland_groups;

/**
 * Returns responses for 'group_node' GroupContent routes.
 */
class UpdateAliasInBatch {
  public static function updateAlias($entities, &$context){
    $message = 'Updating alias for content and media...';
    $results = array();
    $pathGen = \Drupal::service('pathauto.generator');
    foreach ($entities as $entity) {
      $pathGen->updateEntityAlias($entity, "update");
    }
    $context['message'] = $message;
    $context['results'] = $results;
  }

  function updateAliasFinishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One alias updated.', '@count aliases updated.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }
}
