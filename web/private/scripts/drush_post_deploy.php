<?php

sleep(10);
//Clear the cache to make sure database doesn't conflict
echo "Start rebuilding cache...\n";
passthru('drush cr 2>&1');
echo "Done rebuilding cache.\n";
// Apply any database updates required.
echo "Start applying any database updates required (as with running update.php)...\n";
passthru('drush updatedb -y 2>&1');
echo "Done applying any database updates required.\n";
// Import all config changes.
echo "Start importing configuration from yml files...\n";
system('drush config-import -y 2>&1');
echo "Done importing of configuration.\n";
// Run the cron.
echo "Starting cron...\n";
passthru('drush core:cron -y 2>&1');
echo "Done with cron.\n";
//Clear the cache again
echo "Start rebuilding cache...\n";
passthru('drush cr 2>&1');
echo "Done rebuilding cache.\n";
