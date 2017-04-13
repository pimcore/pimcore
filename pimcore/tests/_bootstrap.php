<?php

use Codeception\Util\Autoload;

require_once __DIR__ . '/../../vendor/autoload.php';

Autoload::addNamespace('Pimcore\Tests\Cache', __DIR__ . '/cache');
Autoload::addNamespace('Pimcore\Tests\Model', __DIR__ . '/model');
Autoload::addNamespace('Pimcore\Tests\Unit', __DIR__ . '/unit');
Autoload::addNamespace('Pimcore\Tests\Rest', __DIR__ . '/rest');

if (!defined('TESTS_PATH')) {
    define('TESTS_PATH', __DIR__);
}

define('PIMCORE_TEST', true);
