#!/bin/bash

set -e

# set home directory permissions to be readable by apache
sudo chmod 0755 $(pwd)

echo $INPUT_PIMCORE_TEST_PHP_VERSION1
echo $PIMCORE_TEST_PHP_VERSION

# install apache
sudo apt-get update --allow-unauthenticated
sudo apt-get install apache2 libapache2-mod-fastcgi
sudo apt-get install -y php$PIMCORE_TEST_PHP_VERSION-fpm
sudo a2enmod rewrite actions fastcgi alias

#reset port for webserver
sudo mv /etc/apache2/ports.conf /etc/apache2/ports.conf.default
echo "Listen 8080" | sudo tee /etc/apache2/ports.conf

#remove default apache sites
sudo rm -f /etc/apache2/sites-available/*
sudo rm -f /etc/apache2/sites-enabled/*

# enable pimcore-test.dev config
sudo cp -f .github/server-config/apache-fpm.conf /etc/apache2/sites-available/pimcore-test.dev.conf
sudo ln -s /etc/apache2/sites-available/pimcore-test.dev.conf /etc/apache2/sites-enabled/pimcore-test.dev.conf

VHOSTCFG=/etc/apache2/sites-available/pimcore-test.dev.conf

# configure apache virtual hosts - config was copied above
sudo sed -e "s?%GITHUB_WORKSPACE_DIR%?$(pwd)?g" -i $VHOSTCFG
sudo sed -e "s?%PIMCORE_ENVIRONMENT%?$PIMCORE_ENVIRONMENT?g" -i $VHOSTCFG
sudo sed -e "s?%PIMCORE_TEST_DB_DSN%?$PIMCORE_TEST_DB_DSN?g" -i $VHOSTCFG
sudo sed -e "s?%PIMCORE_TEST_CACHE_REDIS_DATABASE%?$PIMCORE_TEST_CACHE_REDIS_DATABASE?g" -i $VHOSTCFG
sudo sed -e "s?%PIMCORE_TEST_PHP_VERSION%?$PIMCORE_TEST_PHP_VERSION?g" -i $VHOSTCFG

#restart apache
sudo service apache2 restart

#fix file permissions
HTTPDUSER=$(ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1)
sudo setfacl -dR -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX app/config bin composer.json var web
sudo setfacl -R -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX app/config bin composer.json var web