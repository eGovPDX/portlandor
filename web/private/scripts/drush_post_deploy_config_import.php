<?php

// Do NOT run for Dependabot branches
if (preg_match('/^bot-\d+/', $_ENV['PANTHEON_ENVIRONMENT'])) {
    die('Dependabot branch detected. Aborting!');
}


// Import all config changes.
echo "Start importing configuration from yml files...\n";
system('drush config-import -y 2>&1');
echo "Done importing of configuration.\n";
