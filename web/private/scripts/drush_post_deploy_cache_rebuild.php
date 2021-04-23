<?php

// Import all config changes.
echo "Start rebuilding cache...\n";
system('drush cr 2>&1');
echo "Done rebuilding cache.\n";
