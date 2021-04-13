<?php

// Do NOT run for Dependabot branches
if (preg_match('/^bot-\d+/', $_ENV['PANTHEON_ENVIRONMENT'])) {
    die('Dependabot branch detected. Aborting!');
}


// Turn on maintenance mode.
echo "Turn on maintenance mode...\n";
system('drush sset system.maintenance_mode TRUE 2>&1');
echo "Maintenance mode is on.\n";
