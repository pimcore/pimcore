#!/bin/bash

set -eu

mkdir -p var/config

cp -r .github/ci/files/config/. config
cp -r .github/ci/files/templates templates
cp -r .github/ci/files/bin/console bin/console
cp -r .github/ci/files/src src
cp -r .github/ci/files/public public

cp .github/ci/files/extensions.template.php var/config/extensions.php

# temp. until elasticsearch/elasticsearch 7.11 is released
composer config minimum-stability "dev"
composer config prefer-stable true