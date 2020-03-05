#!/bin/bash

echo "Setting up database server ..."


if [ $DATABASE_SERVER ]
then
    # remove the pre-installed MariaDB server
    sudo apt-get purge -y mariadb-*
    sudo rm -rf /var/lib/mysql
    sudo rm -rf /etc/mysql

    sudo apt-get install software-properties-common
    sudo apt-key adv --fetch-keys 'https://mariadb.org/mariadb_release_signing_key.asc'
fi

if [ $DATABASE_SERVER == "mariadb-10.1" ]
then

    sudo add-apt-repository 'deb [arch=amd64,arm64,i386,ppc64el] http://nyc2.mirrors.digitalocean.com/mariadb/repo/10.1/ubuntu xenial main'

    sudo apt-get update
    sudo apt-get install -y mariadb-server-10.1
    sudo systemctl start mysql
    mysql -e "SET GLOBAL innodb_large_prefix=1;"
fi