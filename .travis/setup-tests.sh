#!/bin/bash

set -eu

mkdir -p var/config

cp -r .travis/app app
cp -r .travis/bin/console bin/console
cp -r .travis/web web

cp .travis/extensions.template.php var/config/extensions.php
cp app/config/parameters.example.yml app/config/parameters.yml

# install composer dependencies
composer require symfony/symfony:$SYMFONY_VERSION --no-interaction --no-update --no-scripts
composer install --no-interaction --optimize-autoloader
