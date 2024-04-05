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
  public static function updateAlias($entities, $limit, $orig_group_path, $group_path, &$context)
  {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_index'] = 0;
      $context['sandbox']['max'] = count($entities);
      $context['sandbox']['source_array'] = $entities;
      $context['results'] = [];
    }

    $pathGen = \Drupal::service('pathauto.generator');
    $path_alias_manager = \Drupal::entityTypeManager()->getStorage('path_alias');
    foreach (array_slice($entities, $context['sandbox']['current_index'], $limit) as $entity) {
      $context['message'] = "Processing entity: {$entity->id()}";
      if (empty($entity->path->pathauto)) { // This means custom alias
        // Get aliases in all languages of the system path
        $alias_objects = $path_alias_manager->loadByProperties([
          'path' => "/{$entity->getEntityTypeId()}/{$entity->id()}",
        ]);

        $count = 1;
        foreach($alias_objects as $alias_object) {
          // Replace the first occurance of the original group path with the new one
          $alias_object->alias = str_replace($orig_group_path, $group_path, $alias_object->alias->value, $count);
          $alias_object->save();
        }
      }
      else {
        $pathGen->updateEntityAlias($entity, "update");
      }

      $_SESSION['http_request_count']++;
      $context['sandbox']['progress']++;
      // Assuming you have number for entry within file.
      $context['sandbox']['current_index']++;
      $context['results'] []= "alias updated";
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
