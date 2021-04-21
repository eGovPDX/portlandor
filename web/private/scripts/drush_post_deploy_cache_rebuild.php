<?php

// Do NOT run for Dependabot branches
if (preg_match('/^bot-\d+/', $_ENV['PANTHEON_ENVIRONMENT'])) {
    die('Dependabot branch detected. Aborting!');
}


// Import all config changes.
echo "Start rebuilding cache...\n";
system('drush cr 2>&1');
echo "Done rebuilding cache.\n";
