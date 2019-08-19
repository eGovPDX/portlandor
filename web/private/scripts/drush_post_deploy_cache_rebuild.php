<?php

//Clear the cache to make sure database doesn't conflict
echo "Start rebuilding cache...\n";
passthru('drush cr 2>&1');
echo "Done rebuilding cache.\n";
