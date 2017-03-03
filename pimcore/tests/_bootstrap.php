<?php

require_once __DIR__ . '/../../vendor/autoload.php';

if (!defined('TESTS_PATH')) {
    define('TESTS_PATH', __DIR__);
}

// some general pimcore definition overwrites
define('PIMCORE_ADMIN', true);
define('PIMCORE_DEBUG', true);
define('PIMCORE_DEVMODE', true);
define('PIMCORE_CLASS_DIRECTORY', codecept_output_dir() . 'var/classes');
