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

namespace Pimcore\Model\DataObject\ClassDefinition\Helper;

use Pimcore;

/**
 * @internal
 */
abstract class ClassResolver
{
    private static array $cache;

    protected static function resolve(?string $class, callable $validationCallback = null): ?object
    {
        if (!$class) {
            return null;
        }

        return self::$cache[$class] ??= self::returnValidServiceOrNull(
            str_starts_with($class, '@') ? Pimcore::getContainer()->get(substr($class, 1)) : new $class,
            $validationCallback
        );
    }

    private static function returnValidServiceOrNull(object $service, callable $validationCallback = null): ?object
    {
        if ($validationCallback && !$validationCallback($service)) {
            return null;
        }

        return $service;
    }
}
