<?php

// Do NOT run for Dependabot branches
if (preg_match('/^bot-\d+/', $_ENV['PANTHEON_ENVIRONMENT'])) {
    die('Dependabot branch detected. Aborting!');
}


// Import all config changes.
echo "Start importing configuration from yml files...\n";
system('date +"%T"');
system('drush config-import -y -vvv 2>&1');
system('date +"%T"');
echo "Done importing of configuration.\n";
