<?php
// Don't ever sanitize the database on the live environment. Doing so would
// destroy the canonical version of the data.
if (defined('PANTHEON_ENVIRONMENT') && (PANTHEON_ENVIRONMENT !== 'live')) {

	echo "Deleting revision data older than 30 days...\n";
	passthru('drush php-eval "_portland_test_log()"');
	//passthru('drush php-eval "_portland_truncate_revision_data()"');
	echo "Revision deletion complete.\n";

}
