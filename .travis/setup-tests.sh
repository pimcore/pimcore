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


echo "Starting Install-Script"

# checkout skeleton
git clone https://github.com/pimcore/skeleton.git /tmp/www

# remove .git folder so that composer merge plugin makes symlink correctly
rm -r -f ~/build/pimcore/pimcore/.git

mkdir /tmp/www/dev
mv ~/build/pimcore /tmp/www/dev
ln -s /tmp/www/dev/pimcore ~/build/pimcore


# add config templates
mkdir -p /tmp/www/var/config
cp .travis/system.template.php /tmp/www/var/config/system.php
cp .travis/extensions.template.php /tmp/www/var/config/extensions.php
cp /tmp/www/app/config/parameters.example.yml /tmp/www/app/config/parameters.yml
cp .travis/composer.local.json /tmp/www/composer.local.json

# install composer dependencies
cd /tmp/www
COMPOSER_DISCARD_CHANGES=true COMPOSER_MEMORY_LIMIT=-1 composer install --no-interaction --optimize-autoloader
cd ~/build/pimcore/pimcore