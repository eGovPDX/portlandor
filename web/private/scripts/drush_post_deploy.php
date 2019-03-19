<?php

// Import all config changes.
echo "Start importing configuration from yml files...\n";
passthru('drush config-import -y');
echo "Done importing of configuration.\n";
//Clear all cache
echo "Start rebuilding cache...\n";
passthru('drush cr');
echo "Done rebuilding cache.\n";
// Apply any database updates required.
echo "Start applying any database updates required (as with running update.php)...\n";
passthru('drush updatedb -y');
echo "Done applying any database updates required.\n";
