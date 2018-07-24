#!/bin/bash

set -eu

echo "Starting Install-Script"

# checkout skeleton
# this was added to an extra script because travis had problems when the .travis.yml contained
# the string "sudo"
if [[ "$TRAVIS_SUDO" == "true" ]]
then
    echo "Creating folder in /tmp/www"
    .travis/install-sudo.sh
fi


mkdir /tmp/www
git clone https://github.com/pimcore/skeleton.git /tmp/www
mkdir /tmp/www/dev
mkdir /tmp/www/dev/pimcore

ln -s ~ /tmp/www/dev/pimcore/pimcore

# add config templates
mkdir -p /tmp/www/var/config
cp .travis/system.template.php /tmp/www/var/config/system.php
cp .travis/extensions.template.php /tmp/www/var/config/extensions.php
cp app/config/parameters.example.yml /tmp/www/app/config/parameters.yml

cp .travis/composer.local.json /tmp/www/composer.local.json

# install composer dependencies

cd /tmp/www
composer install --dev --no-interaction --optimize-autoloader
cd ~