<?php

// set max to a value over zero to limit the number of nodes to update for testng purposes.
// set to zero to process all.
$max = 25;

echo "\nStarting group relationship migration for MEDIA.\n";
$nids = \Drupal::entityQuery('media')
        ->accessCheck(FALSE)
        ->execute();
ksort($nids);
$total_nodes = count($nids);
echo $total_nodes . " media entities found.\n\n";

echo str_pad("COUNT", 8) . str_pad("STATUS", 8) . str_pad("ID", 8) . str_pad("GROUPS", 16) . str_pad("BUNDLE", 16) . "TITLE\n";
echo "--------------------------------------------------------------------------------------------------------------------------\n";

$added = 0;
$skipped = 0;
$errors = 0;

foreach($nids as $key => $nid) {
  if ($max > 0 && ($added + $skipped) > $max) {
    break;
  }

  $node = \Drupal\media\Entity\Media::load($nid);
  $ids = \Drupal::entityQuery('group_content')
    ->condition('entity_id', $nid)
    ->accessCheck(FALSE)
    ->execute();

  if (count($ids) > 0) {
    $relations = \Drupal\group\Entity\GroupContent::loadMultiple($ids);
    $gids = [];
    foreach($relations as $rel) {
      if ($rel->getEntity()->getEntityTypeId() == 'media') {
        $gids[] = $rel->getGroup()->id();
      }
    }
    if (!is_null($node->field_display_groups)) {
      foreach($gids as $gid) {
        $node->field_display_groups[] = $gid;
      }

      try {
        $node->save();
        $added += 1;
      } catch (Exception $e) {
        $errors += 1;
        echo str_pad($added + $skipped + $errors, 8) . str_pad("ERROR", 8) . str_pad($nid, 8) . str_pad("", 16) . str_pad(substr($node->bundle(),0,15), 16) . substr($node->name->value, 0, 64) . "\n";
        echo $e->getMessage();
        continue;
      }
      echo str_pad($added + $skipped + $errors, 8) . str_pad("Update", 8) . str_pad($nid, 8) . str_pad(implode("|", $gids), 16) . str_pad(substr($node->bundle(),0,15), 16) . substr($node->name->value, 0, 64) . "\n";
    } else {
      $skipped += 1;
      echo str_pad($added + $skipped + $errors, 8) . str_pad("Skip", 8) . str_pad($nid, 8) . str_pad("No group field", 16) . str_pad(substr($node->bundle(),0,15), 16) . substr($node->name->value, 0, 64) . "\n";
    }
  } else {
    $skipped += 1;
    $node = \Drupal\media\Entity\Media::load($nid);
    echo str_pad($added + $skipped + $errors, 8) . str_pad("Skip", 8) . str_pad($nid, 8) . str_pad("None found", 16) . str_pad(substr($node->bundle(),0,15), 16) . substr($node->name->value, 0, 64) . "\n";
  }
}

echo "\nUpdated " . $added . " nodes.\n";
echo "Skipped " . $skipped . " nodes.\n";
echo $errors . " Errors.\n";
echo "Operation complete.\n";
exit();