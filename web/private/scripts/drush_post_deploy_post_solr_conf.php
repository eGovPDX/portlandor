<?php

// Do NOT run for Dependabot branches
if (preg_match('/^bot-\d+/', $_ENV['PANTHEON_ENVIRONMENT'])) {
    die('Dependabot branch detected. Aborting!');
}


// Run the cron.
echo "Starting to post Solr conf files...\n";
passthru('drush search-api-pantheon:postSchema pantheon_solr8 ../private/solr-conf 2>&1');
echo "Done posting Solr conf files.\n";
