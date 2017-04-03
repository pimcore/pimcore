#!/bin/bash

set -e

# install apache
sudo apt-get update
sudo apt-get install apache2 libapache2-mod-fastcgi
sudo a2enmod rewrite actions fastcgi alias env
sudo rm -f /etc/apache2/sites-available/*
sudo rm -f /etc/apache2/sites-enabled/*

 # set up web server config
if [[ "$TRAVIS_PHP_VERSION" != *"hhvm"* ]]
then
    .travis/setup-fpm.sh
elif [[ "$TRAVIS_PHP_VERSION" == *"hhvm"* ]]
then
    .travis/setup-fpm.sh
else
    echo "No server setup config found for PHP version $TRAVIS_PHP_VERSION"
    exit 1
fi

# enable pimcore-test.dev config
sudo ln -s /etc/apache2/sites-available/pimcore-test.dev.conf /etc/apache2/sites-enabled/pimcore-test.dev.conf

VHOSTCFG=/etc/apache2/sites-available/pimcore-test.dev.conf

# configure apache virtual hosts - config was copied in the individual setup scripts above (FPM/HHVM)
sudo sed -e "s?%TRAVIS_BUILD_DIR%?$(pwd)?g" -i $VHOSTCFG
sudo sed -e "s?%PIMCORE_ENVIRONMENT%?$PIMCORE_ENVIRONMENT?g" -i $VHOSTCFG
sudo sed -e "s?%PIMCORE_TEST_DB_DSN%?$PIMCORE_TEST_DB_DSN?g" -i $VHOSTCFG
sudo sed -e "s?%PIMCORE_TEST_CACHE_REDIS_DATABASE%?$PIMCORE_TEST_CACHE_REDIS_DATABASE?g" -i $VHOSTCFG

sudo service apache2 restart
