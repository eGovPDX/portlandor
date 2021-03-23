<?php

// Do NOT run for Dependabot branches
if (preg_match('/^bot-\d+/', $_ENV['PANTHEON_ENVIRONMENT'])) {
    die('Dependabot branch detected. Aborting!');
}


// Run the cron.
echo "Starting cron...\n";
passthru('drush core:cron -y 2>&1');
echo "Done with cron.\n";
