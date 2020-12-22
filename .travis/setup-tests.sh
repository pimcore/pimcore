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
if [ $COMPOSER_PREFER_LOWEST = 1 ]
then
    composer update --prefer-lowest --prefer-stable -o
else
    composer install -o
fi
