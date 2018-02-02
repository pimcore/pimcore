#!/bin/bash

echo "Setting up FPM ..."

sudo cp -f .travis/php-fpm.conf ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf

echo "cgi.fix_pathinfo = 1" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
~/.phpenv/versions/$(phpenv version-name)/sbin/php-fpm

sudo cp -f .travis/apache-fpm.conf /etc/apache2/sites-available/pimcore-test.dev.conf
