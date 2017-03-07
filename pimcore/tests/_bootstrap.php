<?php

use Codeception\Util\Autoload;
use Symfony\Component\Filesystem\Filesystem;

require_once __DIR__ . '/../../vendor/autoload.php';

Autoload::addNamespace('Pimcore\Tests\Model', __DIR__ . '/model');
Autoload::addNamespace('Pimcore\Tests\Unit', __DIR__ . '/unit');
Autoload::addNamespace('Pimcore\Tests\Rest', __DIR__ . '/rest');

if (!defined('TESTS_PATH')) {
    define('TESTS_PATH', __DIR__);
}

// some general pimcore definition overwrites
define('PIMCORE_DEBUG', true);
define('PIMCORE_DEVMODE', true);

// override and initialize directories
define('PIMCORE_CLASS_DIRECTORY', codecept_output_dir() . 'var/classes');
define('PIMCORE_ASSET_DIRECTORY', codecept_output_dir() . 'var/assets');

$directories = [
    PIMCORE_CLASS_DIRECTORY,
    PIMCORE_ASSET_DIRECTORY
];

$filesystem = new Filesystem();
foreach ($directories as $directory) {
    if (!$filesystem->exists($directory)) {
        $filesystem->mkdir($directory, 0755);
    }
}
