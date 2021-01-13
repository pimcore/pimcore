#!/bin/bash

set -euv

git clone https://github.com/pimcore/pimcore-docs.git tmp-docs/pimcore-docs
cd tmp-docs/pimcore-docs

# check out latest pimcore-docs release
LATEST_VERSION=$(git describe --abbrev=0 --tags)
echo "Checking out latest pimcore-docs release ${LATEST_VERSION}"
git checkout ${LATEST_VERSION}

# install composer dependencies
composer install  --no-interaction --optimize-autoloader

# prepare docs
bin/console prepare --config-file=./config/pimcore-6.json --repository-version=master --repository-version-label=master --repository-version-maintained=true --version-map-file=./versionmap-pimcore.json --version-switch-path-prefix=./ ../../doc

bin/console generate
