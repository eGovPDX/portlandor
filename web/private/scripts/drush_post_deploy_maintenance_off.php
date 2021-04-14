<?php

// Do NOT run for Dependabot branches
if (preg_match('/^bot-\d+/', $_ENV['PANTHEON_ENVIRONMENT'])) {
    die('Dependabot branch detected. Aborting!');
}


// Turn off maintenance mode.
echo "Turn off maintenance mode...\n";
system('drush sset system.maintenance_mode FALSE 2>&1');
echo "Maintenance mode is off.\n";
