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

namespace Pimcore\Model\DataObject\ClassDefinition;

use Pimcore\Model\DataObject\ClassDefinition;

/**
 * In-process cache for included class definition files.
 *
 * Including a definition file on every runtime cache miss is expensive and exhausts
 * file handles in long-running processes which clear the runtime cache periodically.
 * Entries are validated against the definition file's modification time and are
 * invalidated whenever Pimcore writes or deletes a definition file.
 *
 * @internal
 */
final class DefinitionFileCache
{
    /**
     * @var array<string, array{mtime: int, definition: ClassDefinition}>
     */
    private static array $cache = [];

    public static function load(string $definitionFile, bool $force = false): ?ClassDefinition
    {
        clearstatcache(false, $definitionFile);
        $mtime = @filemtime($definitionFile);
        if ($mtime === false) {
            unset(self::$cache[$definitionFile]);

            return null;
        }

        if (
            !$force
            && isset(self::$cache[$definitionFile])
            && self::$cache[$definitionFile]['mtime'] === $mtime
        ) {
            return self::$cache[$definitionFile]['definition'];
        }

        $definition = @include $definitionFile;
        if (!$definition instanceof ClassDefinition) {
            unset(self::$cache[$definitionFile]);

            return null;
        }

        self::$cache[$definitionFile] = ['mtime' => $mtime, 'definition' => $definition];

        return $definition;
    }

    public static function clear(?string $definitionFile = null): void
    {
        if ($definitionFile !== null) {
            unset(self::$cache[$definitionFile]);
        } else {
            self::$cache = [];
        }
    }
}
