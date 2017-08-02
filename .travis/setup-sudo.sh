#!/bin/bash

set -e

# set home directory permissions to be readable by apache
sudo chmod 0755 /home/travis

# install apache
sudo apt-get update
sudo apt-get install apache2 libapache2-mod-fastcgi
sudo a2enmod rewrite actions fastcgi alias env
sudo rm -f /etc/apache2/sites-available/*
sudo rm -f /etc/apache2/sites-enabled/*

# customize php settings
sudo chmod 0755 .travis/setup-fpm.sh
.travis/setup-fpm.sh

# configure apache virtual hosts - config was copied in the individual setup scripts above (FPM)
sudo ln -s /etc/apache2/sites-available/pimcore-test.dev.conf /etc/apache2/sites-enabled/pimcore-test.dev.conf

VHOSTCFG=/etc/apache2/sites-available/pimcore-test.dev.conf

sudo sed -e "s?%TRAVIS_BUILD_DIR%?$(pwd)?g" -i $VHOSTCFG

sudo service apache2 restart
