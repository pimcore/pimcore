#!/bin/bash

set -eu

mkdir -p var/config

cp -r .travis/app app
cp -r .travis/bin/console bin/console
cp -r .travis/web web

cp .travis/system.template.php var/config/system.php
cp .travis/extensions.template.php var/config/extensions.php
cp app/config/parameters.example.yml app/config/parameters.yml

# install composer dependencies
COMPOSER_MEMORY_LIMIT=-1 composer require symfony/symfony:$SYMFONY_VERSION --no-interaction --no-update
COMPOSER_MEMORY_LIMIT=-1 composer install --no-interaction --optimize-autoloader
