<?php

// Turn on maintenance mode.
echo "Turn on maintenance mode...\n";
system('drush sset system.maintenance_mode TRUE 2>&1');
echo "Maintenance mode is on.\n";
