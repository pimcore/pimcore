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

namespace Pimcore\Model\DataObject;

use InvalidArgumentException;

/**
 * Controls the FOR UPDATE row lock taken while a data object is loaded (hydrated).
 *
 * By default, loading a data object locks its data row. Classes for which the lock is disabled are loaded with a plain
 * read instead, and their saves are checked against the change token of the object: a save based on outdated data
 * throws a {@see \Pimcore\Model\Exception\StaleDataException} instead of overwriting the concurrent change.
 *
 * The lock can be disabled per class (including all its subclasses, e.g. Concrete::class for every data object class)
 * or globally, and configured with pimcore.objects.hydration_lock.disabled_for.
 */
final class HydrationLock
{
    private static bool $disabledForAll = false;

    /**
     * @var array<string, true>
     */
    private static array $disabledClasses = [];

    private static int $unlockedLoads = 0;

    /**
     * Disables the hydration lock for the given data object class and its subclasses, or for all objects when no class
     * is given.
     *
     * @throws InvalidArgumentException when the class name is empty
     */
    public static function disable(?string $className = null): void
    {
        if ($className === null) {
            self::$disabledForAll = true;

            return;
        }

        self::$disabledClasses[self::normalizeClassName($className)] = true;
    }

    /**
     * Re-enables the hydration lock for the given class, or lifts a global disable() when no class is given.
     *
     * @throws InvalidArgumentException when the class name is empty
     */
    public static function enable(?string $className = null): void
    {
        if ($className === null) {
            self::$disabledForAll = false;

            return;
        }

        unset(self::$disabledClasses[self::normalizeClassName($className)]);
    }

    /**
     * Whether the hydration lock is disabled for the given object or class, i.e. whether its saves are checked against
     * the change token and throw on outdated data.
     *
     * @param object|class-string $objectOrClass
     */
    public static function isDisabledFor(object|string $objectOrClass): bool
    {
        if (self::$disabledForAll) {
            return true;
        }

        foreach (array_keys(self::$disabledClasses) as $className) {
            if (is_a($objectOrClass, $className, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a load of the given object currently skips the row lock, either because the lock is disabled for its
     * class or because the load runs inside {@see withoutLock()}.
     *
     * @param object|class-string $objectOrClass
     *
     * @internal
     */
    public static function isLockSkippedFor(object|string $objectOrClass): bool
    {
        return self::$unlockedLoads > 0 || self::isDisabledFor($objectOrClass);
    }

    /**
     * Runs the given load without the row lock, e.g. for getById() with ['lock' => false].
     *
     * @template T
     *
     * @param callable(): T $load
     *
     * @return T
     *
     * @internal
     */
    public static function withoutLock(callable $load): mixed
    {
        self::$unlockedLoads++;

        try {
            return $load();
        } finally {
            self::$unlockedLoads--;
        }
    }

    /**
     * Restores the default: the hydration lock is enabled for all classes.
     *
     * @internal
     */
    public static function reset(): void
    {
        self::$disabledForAll = false;
        self::$disabledClasses = [];
        self::$unlockedLoads = 0;
    }

    private static function normalizeClassName(string $className): string
    {
        $className = ltrim($className, '\\');
        if ($className === '') {
            throw new InvalidArgumentException('The class name to disable the hydration lock for must not be empty.');
        }

        return $className;
    }
}
