#!/bin/bash

set -eu

mkdir -p var/config

cp -r .travis/config/. config
cp -r .travis/templates templates
cp -r .travis/bin/console bin/console
cp -r .travis/src src
cp -r .travis/public public

cp .travis/extensions.template.php var/config/extensions.php

# install composer dependencies
composer self-update --2
composer require symfony/symfony:$SYMFONY_VERSION --no-interaction --no-update --no-scripts
composer install --no-interaction --optimize-autoloader
