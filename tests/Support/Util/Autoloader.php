<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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
