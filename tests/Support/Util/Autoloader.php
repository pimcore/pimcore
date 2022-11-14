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

namespace Pimcore\Tests\Support\Util;

use Codeception\Util\Autoload;

class Autoloader extends Autoload
{
    protected static bool $reg = false;

    public static function addNamespace(string $prefix, string $baseDir, bool $prepend = false): void
    {
        if (!self::$reg) {
            spl_autoload_register([__CLASS__, 'load'], true, true);
            self::$reg = true;
        }

        parent::addNamespace($prefix, $baseDir, $prepend);
    }
}
