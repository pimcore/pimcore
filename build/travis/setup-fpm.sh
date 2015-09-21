#!/bin/bash

echo "Setting up FPM ..."

phpenv config-add build/travis/php.ini

sudo cp -f build/travis/php-fpm.conf ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf

echo "cgi.fix_pathinfo = 1" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
~/.phpenv/versions/$(phpenv version-name)/sbin/php-fpm

sudo cp -f build/travis/apache-fpm.conf /etc/apache2/sites-available/default
