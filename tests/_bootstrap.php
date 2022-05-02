<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

use Pimcore\Tests\Util\Autoloader;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    define('PIMCORE_PROJECT_ROOT', __DIR__ . '/..');
} elseif (file_exists(__DIR__ . '/../../../../vendor/autoload.php')) {
    define('PIMCORE_PROJECT_ROOT', __DIR__ . '/../../../..');
} elseif (getenv('PIMCORE_PROJECT_ROOT')) {
    if (file_exists(getenv('PIMCORE_PROJECT_ROOT') . '/vendor/autoload.php')) {
        define('PIMCORE_PROJECT_ROOT', getenv('PIMCORE_PROJECT_ROOT'));
    } else {
        throw new \Exception('Invalid Pimcore project root "' . getenv('PIMCORE_PROJECT_ROOT') . '"');
    }
} else {
    throw new \Exception('Unknown configuration! Pimcore project root not found, please set env variable PIMCORE_PROJECT_ROOT.');
}

$_ENV['PIMCORE_WRITE_TARGET_IMAGE_THUMBNAILS'] = 'settings-store';
$_ENV['PIMCORE_WRITE_TARGET_CUSTOM_REPORTS'] = 'settings-store';
$_ENV['PIMCORE_WRITE_TARGET_VIDEO_THUMBNAILS'] = 'settings-store';
$_ENV['PIMCORE_WRITE_TARGET_DOCUMENT_TYPES'] = 'settings-store';
$_ENV['PIMCORE_WRITE_TARGET_WEB_TO_PRINT'] = 'settings-store';
$_ENV['PIMCORE_WRITE_TARGET_PREDEFINED_PROPERTIES'] = 'settings-store';
$_ENV['PIMCORE_WRITE_TARGET_PREDEFINED_ASSET_METADATA'] = 'settings-store';
$_ENV['PIMCORE_WRITE_TARGET_STATICROUTES'] = 'settings-store';
$_ENV['PIMCORE_WRITE_TARGET_PERSPECTIVES'] = 'settings-store';
$_ENV['PIMCORE_WRITE_TARGET_CUSTOM_VIEWS'] = 'settings-store';

include PIMCORE_PROJECT_ROOT . '/vendor/autoload.php';
\Pimcore\Bootstrap::setProjectRoot();
\Pimcore\Bootstrap::bootstrap();
\Pimcore\Bootstrap::kernel();

Autoloader::addNamespace('Pimcore\Model\DataObject', __DIR__ . '/_output/var/classes/DataObject');
Autoloader::addNamespace('Pimcore\Tests', __DIR__);
Autoloader::addNamespace('Pimcore\Tests', __DIR__ . '/_support');

if (!defined('PIMCORE_TEST')) {
    define('PIMCORE_TEST', true);
}
