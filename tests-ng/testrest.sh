#!/bin/bash

./phpunit --verbose --bootstrap /home/pimcore/www/tests-ng/bootstrap.php --verbose --log-json /home/pimcore/www/tests-ng/output/log.xml TestSuite_Rest_AllTests

