#!/bin/bash

set -eu

mkdir -p var/config

cp -r .github/ci/files/config/. config
mkdir -p config/local/
cp -r .github/ci/files/templates/. templates
cp -r .github/ci/files/bin/console bin/console
cp -r .github/ci/files/src/. src
cp -r .github/ci/files/public/. public
cp -r .github/ci/files/var/classes/. var/classes
cp .github/ci/files/.env ./

if [ ${PIMCORE_STORAGE:-local} = "minio" ]; then
    cp .github/ci/files/minio-flysystem.yaml config/local/
    composer require -n --no-update league/flysystem-aws-s3-v3
fi

composer require --no-update webmozarts/console-parallelization:^1.2.0