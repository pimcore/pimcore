#!/bin/bash

docker compose down -v --remove-orphans
docker compose up -d

docker compose exec php .github/ci/scripts/setup-pimcore-environment.sh
docker compose exec php composer update

docker compose exec php touch config/dao-classmap.php
docker compose exec php ./bin/console internal:model-dao-mapping-generator

printf "\n\n\n================== \n"
printf "Run 'docker compose exec php vendor/bin/codecept run -vv' to re-run the tests.\n"
printf "Run 'docker compose down -v --remove-orphans' to shutdown container and cleanup.\n\n"