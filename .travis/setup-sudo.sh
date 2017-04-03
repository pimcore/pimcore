#!/bin/bash

set -e

# install apache
sudo apt-get update
sudo apt-get install apache2 libapache2-mod-fastcgi
sudo a2enmod rewrite actions fastcgi alias env

 # customize php settings
if [[ "$TRAVIS_PHP_VERSION" != *"hhvm"* ]]; then .travis/setup-fpm.sh; fi
if [[ "$TRAVIS_PHP_VERSION" == *"hhvm"* ]]; then .travis/setup-hhvm.sh; fi

# configure apache virtual hosts - config was copied in the individual setup scripts above (FPM/HHVM)
sudo sed -e "s?%TRAVIS_BUILD_DIR%?$(pwd)?g" --in-place /etc/apache2/sites-available/default
sudo service apache2 restart
