#!/bin/bash

echo "Setting up HHVM ..."

hhvm -m daemon -vServer.Type=fastcgi -vServer.Port=9000 -vServer.FixPathInfo=true -vLog.File=/tmp/hhvm.log -vServer.GzipCompressionLevel=0

sudo cp -f .travis/apache-hhvm.conf /etc/apache2/sites-available/pimcore-test.dev.conf
