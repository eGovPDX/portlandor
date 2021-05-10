<?php

// Apply any database updates required.
echo "Start applying any database updates required (as with running update.php)...\n";
passthru('drush updatedb -y 2>&1');
echo "Done applying any database updates required.\n";
