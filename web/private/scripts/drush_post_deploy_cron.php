<?php

// Run the cron.
echo "Starting cron...\n";
passthru('drush core:cron -y 2>&1');
echo "Done with cron.\n";
