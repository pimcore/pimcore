#!/bin/bash

set -eu

# --- DOC -----
#
# Folder structure is as follows:
# - Pimcore repository is checked out by travis to /home/travis/pimcore/pimcore
# - Empty Pimcore skeleton is checkout out to /tmp/www
# - Symlink /tmp/www/dev/pimcore/pimcore is created to point to Pimcore repository
#   checked out by travis (make sure we are testing the correct Pimcore version)
# - Apache document root points to /tmp/www/web
# - Composer dependencies are installed to /tmp/www/vendor
# - Additional Symlink /home/travis/vendor points to /tmp/www/vendor
#   (to make relative paths in Pimcore repository work)
#
# --- END DOC -----


echo "Starting Install-Script"

# checkout skeleton
git clone https://github.com/pimcore/skeleton.git /tmp/www
#mkdir /tmp/www/dev
# mkdir /tmp/www/dev/pimcore
rm -r -f ~/build/pimcore/pimcore/.git


#mkdir -p /tmp/www/vendor/pimcore
#ln -s ~/build/pimcore/pimcore /tmp/www/vendor/pimcore/pimcore
#ln -s ~/build/pimcore /tmp/www/dev/pimcore

# add config templates
mkdir -p /tmp/www/var/config
cp .travis/system.template.php /tmp/www/var/config/system.php
cp .travis/extensions.template.php /tmp/www/var/config/extensions.php
cp /tmp/www/app/config/parameters.example.yml /tmp/www/app/config/parameters.yml

cp .travis/composer.local.json /tmp/www/composer.local.json

# install composer dependencies

cd /tmp/www
COMPOSER_DISCARD_CHANGES=true COMPOSER_MEMORY_LIMIT=-1 composer update -d /tmp/www --no-interaction --optimize-autoloader -vvv
cd ~/build/pimcore/pimcore

ln -s /tmp/www/vendor ~

echo "----list----"
cat /tmp/www/composer.local.json
#ls -la /tmp/www/dev/pimcore
ls -la /tmp/www/vendor/pimcore
ls -la ~
#ls -la /tmp/www/dev/pimcore/pimcore
cat ~/build/pimcore/pimcore/composer.json


