<?php

// Do NOT run for Dependabot branches
if (preg_match('/^bot-\d+/', $_ENV['PANTHEON_ENVIRONMENT'])) {
    die('Dependabot branch detected. Aborting!');
}


//Clear the cache to make sure database doesn't conflict
echo "Start rebuilding cache...\n";
passthru('drush cr 2>&1');
echo "Done rebuilding cache.\n";
