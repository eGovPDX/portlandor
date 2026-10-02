<?php

/**
 * @file
 * Post update functions for the Portland module.
 */

/**
 * Delete City Section terms so config import can remove the vocabulary.
 */
function portland_post_update_delete_city_section_terms() {
  $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $tids = $storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('vid', 'city_section')
    ->execute();
  if ($tids) {
    $storage->delete($storage->loadMultiple($tids));
  }
  return t('Deleted @count City Section terms.', ['@count' => count($tids)]);
}
