#!/bin/bash

echo "Setting up database server ..."

if [ $DATABASE_SERVER == "mariadb-10.1" ]
then
    sudo systemctl stop mysql
    sudo apt-get install software-properties-common
    sudo apt-key adv --fetch-keys 'https://mariadb.org/mariadb_release_signing_key.asc'
    sudo add-apt-repository 'deb [arch=amd64,arm64,i386,ppc64el] http://mirrors.piconets.webwerks.in/mariadb-mirror/repo/10.1/ubuntu xenial main'

    sudo apt update
    sudo apt install mariadb-server
fi