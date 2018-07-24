#!/bin/bash

set -eu

# checkout skeleton
# this was added to an extra script because travis had problems when the .travis.yml contained
# the string "sudo"
if [[ "$TRAVIS_SUDO" == "true" ]]
then
    echo "Creating folder in /var/www"
    .travis/install-sudo.sh
fi



git clone https://github.com/pimcore/skeleton.git /var/www
mkdir /var/www/dev
mkdir /var/www/dev/pimcore

ln -s ~ /var/www/dev/pimcore/pimcore

# add config templates
mkdir -p /var/www/var/config
cp .travis/system.template.php /var/www/var/config/system.php
cp .travis/extensions.template.php /var/www/var/config/extensions.php
cp app/config/parameters.example.yml /var/www/app/config/parameters.yml

cp .travis/composer.local.json /var/www/composer.local.json

# install composer dependencies

cd /var/www
composer install --dev --no-interaction --optimize-autoloader
cd ~