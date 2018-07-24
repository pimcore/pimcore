#!/bin/bash

set -eu

echo "Starting Install-Script"

# checkout skeleton
mkdir /tmp/www
git clone https://github.com/pimcore/skeleton.git /tmp/www
mkdir /tmp/www/dev
mkdir /tmp/www/dev/pimcore

ln -s ~ /tmp/www/dev/pimcore/pimcore

# add config templates
mkdir -p /tmp/www/var/config
cp .travis/system.template.php /tmp/www/var/config/system.php
cp .travis/extensions.template.php /tmp/www/var/config/extensions.php
cp /tmp/www/app/config/parameters.example.yml /tmp/www/app/config/parameters.yml

cp .travis/composer.local.json /tmp/www/composer.local.json

# install composer dependencies

cd /tmp/www
COMPOSER_MEMORY_LIMIT=-1 composer install --dev --no-interaction --optimize-autoloader
cd ~

ln -s ~ /tmp/www/vendor