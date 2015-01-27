#!/bin/bash

hhvm -m daemon -vServer.Type=fastcgi -vServer.Port=9000 -vServer.FixPathInfo=true

sudo cp -f ~/build/travis/apache-hhvm.conf /etc/apache2/sites-available/default
