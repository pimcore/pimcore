#!/bin/bash

set -e

if [ "$#" -ne 1 ]; then
    >&2 echo "No path to Pimcore installation provided"
    exit 1
fi

WD=$1
if [ ! -d "$WD" ]; then
    >&2 echo "Directory $WD does not exist"
    exit 2
fi


cd $WD
CWD=`pwd`

if [ -d "var/email" ]; then
    >&2 echo "$CWD was already migrated"
    exit 3
fi


if [ ! -d "website" ]; then
    >&2 echo "No website directory found"
    exit 5
fi

DIRS=(var/logs/pimcore var/email)
for dir in ${DIRS[@]}; do
        if [ -d "$dir" ]; then
        >&2 echo "$dir already exists and would be overwritten - please remove before proceeding"
            exit 6
        fi
done

echo "Prerequisites are OK - starting migration..."
echo ""

set -v

echo "move old pimcore directory"
mv pimcore pimcore4

echo "download latest pimcore build..."
wget https://www.pimcore.org/download-5/pimcore-latest.zip

echo "unzip latest pimcore build"
unzip pimcore-latest.zip


# create config directories
#mkdir -p app/config/pimcore

# create var directories
mkdir -p var/cache/pimcore
mkdir -p var/email
mkdir -p var/logs
mkdir -p var/recyclebin
mkdir -p var/sessions
mkdir -p var/system
mkdir -p var/tmp

# create website/var directories
mkdir -p web/var
mkdir -p web/var/tmp

# create legacy directory
mkdir legacy

# move config files to new location
mv website/var/config/* var/config
mv website/config/* app/config/pimcore

# move private var files to new location
mv website/var/classes/* var/classes
if [ "$(ls -A website/var/versions/)" ]; then
   mv website/var/versions/* var/versions
fi
mv website/var/log var/logs/pimcore
if [ -d "website/var/log" ]; then
   mv website/var/recyclebin/* var/recyclebin
fi
if [ -d "website/var/email" ]; then
  mv website/var/email var/email
fi

# move assets to document root
mv website/var/assets/* web/var/assets/

# move website and plugins to legacy
mv website legacy/website

if [ -d "plugins" ]; then
    mv plugins legacy/plugins
fi

echo "Migration succeeded...you should run composer update now"

