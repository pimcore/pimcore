#!/bin/bash

set -eu

# add config templates
mkdir -p var/config
cp .travis/system.template.php var/config/system.php
cp .travis/extensions.template.php var/config/extensions.php
cp app/config/parameters.example.yml app/config/parameters.yml

# install composer dependencies
composer install --no-interaction --optimize-autoloader
