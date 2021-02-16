<?php

// Do NOT run for Dependabot branches
if (preg_match('/^bot-\d+/', $_ENV['PANTHEON_ENVIRONMENT'])) {
    die('Dependabot branch detected. Aborting!');
}

//Clear the cache to make sure database doesn't conflict
echo "Start running drush deploy...\n";
passthru('drush deploy -v 2>&1');
echo "Done running drush deploy.\n";
