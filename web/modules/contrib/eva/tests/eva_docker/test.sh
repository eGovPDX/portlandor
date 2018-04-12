#!/bin/bash
. .env

docker exec -it $CONTAINER bash -c "vendor/drush/drush/drush en -y simpletest"

docker exec -it $CONTAINER bash -c "test -d /var/www/html/sites/simpletest || mkdir /var/www/html/sites/simpletest && chmod 777 /var/www/html/sites/simpletest"
docker exec -it $CONTAINER bash -c "test -d /var/www/html/sites/default/files/simpletest || mkdir -p /var/www/html/sites/default/files/simpletest && chmod -R 777 /var/www/html/sites/default/files"
docker exec -it $CONTAINER bash -c "chown -R www-data /var/www/html"

for class in $TEST_CLASSES; do
    docker exec -it $CONTAINER bash -c "sudo -u www-data php core/scripts/run-tests.sh --browser --url http://drupal/ --class \"\Drupal\Tests\\$MODULE\Functional\\$class\""
done