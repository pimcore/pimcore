#!/bin/bash

docker-compose down -v --remove-orphans
docker-compose up -d

docker-compose exec php .github/ci/scripts/setup-pimcore-environment.sh

docker-compose exec php composer update

printf "\n\n\n================== \n"
printf "Run 'docker-compose exec php vendor/bin/codecept run -vv' to re-run the tests.\n"
printf "Run 'docker-compose down -v --remove-orphans' to shutdown container and cleanup.\n\n"