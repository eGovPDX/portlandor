<?php

// set max to a value over zero to limit the number of nodes to update for testng purposes.
$max = 0;

echo "Starting group relationship migration.\n";
echo "Disabling mail send.\n";
exec('drush -y pm-enable devel');
exec('drush -y config-set system.mail interface.default devel_mail_log');

$nids = \Drupal::entityQuery('node')
        ->accessCheck(FALSE)
        ->execute();
ksort($nids);
$total_nodes = count($nids);
echo $total_nodes . " nodes found.\n\n";

echo str_pad("COUNT", 8) . str_pad("STATUS", 8) . str_pad("ID", 8) . str_pad("GROUPS", 16) . str_pad("BUNDLE", 16) . "TITLE\n";
echo "--------------------------------------------------------------------------------------------------------------------------\n";

$added = 0;
$skipped = 0;

foreach($nids as $key => $nid) {
  if ($max > 0 && ($added + $skipped) > $max) {
    break;
  }

  $ids = \Drupal::entityQuery('group_content')
    ->condition('entity_id', $nid)
    ->execute();

  if (count($ids) > 0) {
    $relations = \Drupal\group\Entity\GroupContent::loadMultiple($ids);
    $gids = [];
    foreach($relations as $rel) {
      if ($rel->getEntity()->getEntityTypeId() == 'node') {
        $gids[] = $rel->getGroup()->id();
      }
    }
    $node = \Drupal\node\Entity\Node::load($nid);
    if (!is_null($node->field_display_groups)) {
      foreach($gids as $gid) {
        $node->field_display_groups[] = $gid;
      }

      $node->save();
      $added += 1;
      echo str_pad($added + $skipped, 8) . str_pad("Added", 8) . str_pad($nid, 8) . str_pad(implode("|", $gids), 16) . str_pad(substr($node->type->entity->label(),0,15), 16) . substr($node->title->value, 0, 64) . "\n";
    } else {
      $skipped += 1;
      echo str_pad($added + $skipped, 8) . str_pad("Skipped", 8) . str_pad($nid, 8) . str_pad("", 16) . str_pad(substr($node->type->entity->label(),0,15), 16) . substr($node->title->value, 0, 64) . "\n";
    }
  }
}

echo "\nUpdated " . $added . " nodes.\n";
echo "Skipped " . $skipped . " nodes.\n";
echo "Re-enabling mail send.\n";
exec('drush -y config-set system.mail interface.default php_mail');
exec('drush -y pm-uninstall devel');
echo "Operation complete.\n";