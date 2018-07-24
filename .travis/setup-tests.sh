#!/bin/bash

set -eu

# --- DOC -----
#
# Folder structure is as follows:
# - Pimcore repository is checked out by travis to /home/travis/build
# - Empty Pimcore skeleton is checkout out to /tmp/www
# - Symlink /tmp/www/dev/pimcore/pimcore is created to point to Pimcore repository
#   checked out by travis (make sure we are testing the correct Pimcore version)
# - Apache document root points to /tmp/www/web
# - Composer dependencies are installed to /tmp/vendor
# - Additional Symlink /home/travis/vendor points to /tmp/vendor
#   (to make relative paths in Pimcore repository work)
#
# --- END DOC -----


echo "Starting Install-Script"

# checkout skeleton

if [ ! -d /tmp/vendor  ]; then
  mkdir /tmp/vendor
fi

git clone https://github.com/pimcore/skeleton.git /tmp/www
mkdir /tmp/www/dev
mkdir /tmp/www/dev/pimcore

ln -s /tmp/vendor /tmp/www

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

ln -s /tmp/vendor ~