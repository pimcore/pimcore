#!/bin/bash

mkdir -p output

php phpunit.php --verbose --bootstrap bootstrap.php --log-json output/log.xml SingleTest

