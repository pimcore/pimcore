<?php

use Pimcore\Tests\Util\Autoloader;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    include __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
    include __DIR__ . '/../../../../vendor/autoload.php';
} elseif (getenv('PIMCORE_PROJECT_ROOT') != '' && file_exists(getenv('PIMCORE_PROJECT_ROOT') . '/vendor/autoload.php')) {
    include getenv('PIMCORE_PROJECT_ROOT') . '/vendor/autoload.php';
} elseif (getenv('PIMCORE_PROJECT_ROOT') != '') {
    throw new \Exception('Invalid Pimcore project root "' . getenv('PIMCORE_PROJECT_ROOT') . '"');
} else {
    throw new \Exception('Unknown configuration! Pimcore project root not found, please set env variable PIMCORE_PROJECT_ROOT.');
}

\Pimcore\Bootstrap::setProjectRoot();
\Pimcore\Bootstrap::bootstrap();

Autoloader::addNamespace('Pimcore\Model\DataObject', __DIR__ . '/_output/var/classes/DataObject');
Autoloader::addNamespace('Pimcore\Tests\Cache', __DIR__ . '/Cache');
Autoloader::addNamespace('Pimcore\Tests\Ecommerce', __DIR__ . '/Ecommerce');
Autoloader::addNamespace('Pimcore\Tests\Model', __DIR__ . '/Model');
Autoloader::addNamespace('Pimcore\Tests\Unit', __DIR__ . '/Unit');
Autoloader::addNamespace('Pimcore\Tests\Rest', __DIR__ . '/Rest');
Autoloader::addNamespace('Pimcore\Tests\Service', __DIR__ . '/Service');

if (!defined('TESTS_PATH')) {
    define('TESTS_PATH', __DIR__);
}

if (!defined('TESTS_PATH')) {
    define('PIMCORE_TEST', true);
}
