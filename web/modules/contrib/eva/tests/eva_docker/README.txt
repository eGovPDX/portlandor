The purpose of this directory is local replication of reported issues for EVA. 
It contains a Docker network and a composer-specified Drupal installation. The
EVA module is linked from the parent directory (i.e., local changes and patches
may be tested in real time).

Prerequisites:

- Docker

Quickstart:

- docker-compose up
- ./dcomposer install
- ./ddrush site-install standard --site-name=test
- ./ddrush en -y eva_test
- ./test.sh
