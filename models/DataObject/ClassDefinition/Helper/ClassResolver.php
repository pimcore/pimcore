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

namespace Pimcore\Model\DataObject\ClassDefinition\Helper;

use Pimcore;

/**
 * @internal
 */
abstract class ClassResolver
{
    private static array $cache;

    protected static function resolve(?string $class, ?callable $validationCallback = null): ?object
    {
        if (!$class) {
            return null;
        }

        return self::$cache[$class] ??= self::returnValidServiceOrNull(
            str_starts_with($class, '@') ? Pimcore::getContainer()->get(substr($class, 1)) : new $class,
            $validationCallback
        );
    }

    private static function returnValidServiceOrNull(object $service, ?callable $validationCallback = null): ?object
    {
        if ($validationCallback && !$validationCallback($service)) {
            return null;
        }

        return $service;
    }
}
