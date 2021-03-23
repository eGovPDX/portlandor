<?php

// Do NOT run for Dependabot branches
if (preg_match('/^bot-\d+/', $_ENV['PANTHEON_ENVIRONMENT'])) {
    die('Dependabot branch detected. Aborting!');
}


// Apply any database updates required.
echo "Start applying any database updates required (as with running update.php)...\n";
passthru('drush updatedb -y 2>&1');
echo "Done applying any database updates required.\n";
