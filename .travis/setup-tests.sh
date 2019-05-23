#!/bin/bash

set -eu

mkdir -p var/config

cp .travis/system.template.php var/config/system.php
cp .travis/extensions.template.php var/config/extensions.php
cp app/config/parameters.example.yml app/config/parameters.yml

# install composer dependencies
COMPOSER_MEMORY_LIMIT=-1 composer install --no-interaction --optimize-autoloader
COMPOSER_MEMORY_LIMIT=-1 composer require symfony/symfony:$SYMFONY_VERSION --no-interaction --optimize-autoloader
