<?php

// Import all config changes.
echo "Start importing configuration from yml files...\n";
system('date +"%T"');
$exit_code = 0;
system('drush config-import -y -vvv 2>&1', $exit_code);
// Retry config import if the first one fails
if( $exist_code != 0) system('drush config-import -y -vvv 2>&1');
system('date +"%T"');
echo "Done importing of configuration.\n";
