#!/bin/bash

set -euv

rm -rf tmp-docs
git clone https://github.com/pimcore/pimcore-docs.git tmp-docs/pimcore-docs
cd tmp-docs/pimcore-docs
composer install  --no-interaction --optimize-autoloader
bin/console prepare ../../doc
