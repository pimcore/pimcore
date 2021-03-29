#!/bin/bash

set -eu -o xtrace

mkdir -p var/config

cp -r .github/ci/files/config/. config
mkdir -p config/local/
cp -r .github/ci/files/templates templates
cp -r .github/ci/files/bin/console bin/console
cp -r .github/ci/files/src src
cp -r .github/ci/files/public public
cp .github/ci/files/extensions.template.php var/config/extensions.php
cp .github/ci/files/.env ./

if [ $PIMCORE_STORAGE = "minio" ]; then
    cp .github/ci/files/minio-flysystem.yml config/local/
    composer require -n --no-update league/flysystem-aws-s3-v3
fi

# temp. until elasticsearch/elasticsearch 7.11 is released
composer config minimum-stability "dev"
composer config prefer-stable true