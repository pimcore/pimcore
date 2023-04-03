<?php
declare(strict_types=1);

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

use Pimcore\Tests\Support\Util\Autoloader;

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

include PIMCORE_PROJECT_ROOT . '/vendor/autoload.php';
\Pimcore\Bootstrap::setProjectRoot();
\Pimcore\Bootstrap::bootstrap();

Autoloader::addNamespace('Pimcore\Model\DataObject', __DIR__ . '/_output/var/classes/DataObject');
Autoloader::addNamespace('Pimcore\Tests', __DIR__);

if (!defined('PIMCORE_TEST')) {
    define('PIMCORE_TEST', true);
}
