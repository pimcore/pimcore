#!/bin/bash

set -euv

rm -rf tmp-docs
git clone https://github.com/pimcore/pimcore-docs.git tmp-docs/pimcore-docs
cd tmp-docs/pimcore-docs

# check out latest pimcore-docs release
LATEST_VERSION=$(git describe --abbrev=0)
echo "Checking out latest pimcore-docs release ${LATEST_VERSION}"
git checkout ${LATEST_VERSION}

# install composer dependencies
composer install  --no-interaction --optimize-autoloader

# prepare docs
bin/console prepare ../../doc
