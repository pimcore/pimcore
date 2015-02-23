#!/bin/bash

mkdir -p output

hhvm phpunit.php --verbose --bootstrap bootstrap.php --log-json output/log.xml AllTests

