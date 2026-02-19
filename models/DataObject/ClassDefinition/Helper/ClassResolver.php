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

use Error;
use Pimcore;
use Pimcore\Logger;

/**
 * @internal
 */
abstract class ClassResolver
{
    private static array $cache;

    protected static function resolve(
        ?string $class,
        ?callable $validationCallback = null,
        bool $showError = true
    ): ?object {
        if (!$class) {
            return null;
        }

        $return = null;
        if ($showError) {
            $return = self::$cache[$class] ??= self::returnValidServiceOrNull(
                str_starts_with($class, '@') ? Pimcore::getContainer()->get(substr($class, 1)) : new $class,
                $validationCallback
            );
        }

        try {
            $return = self::$cache[$class] ??= self::returnValidServiceOrNull(
                str_starts_with($class, '@') ? Pimcore::getContainer()->get(substr($class, 1)) : new $class,
                $validationCallback
            );
        } catch (Error $e) {
            Logger::error($e->getMessage());
        }

        return $return;
    }

    private static function returnValidServiceOrNull(object $service, ?callable $validationCallback = null): ?object
    {
        if ($validationCallback && !$validationCallback($service)) {
            return null;
        }

        return $service;
    }
}
