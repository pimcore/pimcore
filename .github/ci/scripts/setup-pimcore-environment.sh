#!/bin/bash

set -eu

mkdir -p var/config

cp -r .github/ci/files/config/. config
cp -r .github/ci/files/templates templates
cp -r .github/ci/files/bin/console bin/console
cp -r .github/ci/files/src src
cp -r .github/ci/files/public public

cp .github/ci/files/extensions.template.php var/config/extensions.php
