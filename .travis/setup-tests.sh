#!/bin/bash

set -eu

mkdir -p var/config

cp -r .travis/app app
cp -r .travis/bin/console bin/console
cp -r .travis/web web

cp .travis/extensions.template.php var/config/extensions.php
cp app/config/parameters.example.yml app/config/parameters.yml

# install composer dependencies
composer self-update --2
if [ $COMPOSER_PREFER_LOWEST = 1 ]
then
    composer update --prefer-lowest --prefer-stable -o
else
    composer install -o
fi
