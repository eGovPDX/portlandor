<?php

// Import all config changes.
echo "Start importing configuration from yml files...\n";
system('drush config-import -y 2>&1');
echo "Done importing of configuration.\n";
