<?php

namespace Drupal\portland_groups;

use \Drupal\pathauto\PathautoState;

/**
 * Helper class to update alias in batch.
 * 
 * https://drupal.stackexchange.com/questions/241744/batch-api-timeout-during-initialization
 */
class UpdateAliasInBatch
{
  /**
   * A static function that updates content, media, subgroup aliases when the parent group's group_path is updated
   */
  public static function updateGroupContentAlias($entities, $limit, &$context)
  {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_index'] = 0;
      $context['sandbox']['max'] = count($entities);
      $context['sandbox']['source_array'] = $entities;
      $context['results'] = [];
    }

    $pathGen = \Drupal::service('pathauto.generator');
    foreach (array_slice($entities, $context['sandbox']['current_index'], $limit) as $entity) {
      $context['message'] = "Processing entity: {$entity->id()}";
      // Update content and media aliases
      if (!empty($entity->path->pathauto)) { // check if the alias is auto-generated
        $pathGen->updateEntityAlias($entity, "update");
      }

      $_SESSION['http_request_count']++;
      $context['sandbox']['progress']++;
      // Assuming you have number for entry within file.
      $context['sandbox']['current_index']++;
      $context['results'][] = "alias updated";
    }

    // Inform the batch engine that we are not finished,`
    // and provide an estimation of the completion level we reached.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  function updateAliasFinishedCallback($success, $results, $operations)
  {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One alias updated.',
        '@count aliases updated.'
      );
    } else {
      $message = t('Finished with an error.');
    }

    \Drupal::messenger()->addMessage($message);
  }
}