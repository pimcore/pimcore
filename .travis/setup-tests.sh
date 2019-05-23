#!/bin/bash

set -eu

# --- DOC -----
#
# Folder structure is as follows:
# - Pimcore repository is checked out by travis to /home/travis/pimcore/pimcore and then moved to /tmp/www/dev/pimcore/pimcore
#   (necessary to make several relative path definitions to vendor, app, var in Pimcore work
# - Symlink /home/travis/pimcore points to /tmp/www/dev/pimcore for compatibility
# - Empty Pimcore skeleton is checkout out to /tmp/www
# - Composer dependencies are installed to /tmp/www/vendor
# - Via composer.local.json checked out Pimcore sources (/tmp/www/dev/pimcore/pimcore) are linked to vendor folder
# - Apache document root points to /tmp/www/web
#
# --- END DOC -----

mkdir -p var/config

cp .travis/system.template.php var/config/system.php
cp .travis/extensions.template.php var/config/extensions.php
cp app/config/parameters.example.yml app/config/parameters.yml

# install composer dependencies
COMPOSER_MEMORY_LIMIT=-1 composer install --no-interaction --optimize-autoloader
COMPOSER_MEMORY_LIMIT=-1 composer require symfony/symfony:$SYMFONY_VERSION --no-interaction --optimize-autoloader
