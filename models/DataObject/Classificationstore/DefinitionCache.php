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

namespace Pimcore\Model\DataObject\Classificationstore;

/**
 * @internal
 */
class DefinitionCache
{
    public static array $cache = [];

    public static function get(int $id, string $type = 'key'): ?KeyConfig
    {
        $key = $type . $id;
        $config = isset(self::$cache[$key]) ? self::$cache[$key] : null;
        if ($config) {
            return $config;
        }

        $config = KeyConfig::getById($id);

        if (!$config) {
            return null;
        }
        self::put($config);

        return $config;
    }

    /**
     * Reads the key configs that are not held yet with a single statement - a second one only if
     * some of them are disabled, which the listing answers separately from the enabled ones.
     *
     * @param int[] $ids
     */
    public static function load(array $ids): void
    {
        $missing = self::filterHeldKeys($ids);
        if (!$missing) {
            return;
        }

        self::loadKeys($missing, false);

        $missing = self::filterHeldKeys($missing);
        if ($missing) {
            self::loadKeys($missing, true);
        }
    }

    public static function put(GroupConfig|KeyConfig $config): void
    {
        $type = self::getType($config);
        if (!$type) {
            return;
        }
        $key = $type . $config->getId();
        self::$cache[$key] = $config;
    }

    public static function clear(GroupConfig|KeyConfig|null $config): void
    {
        if ($config) {
            $type = self::getType($config);
            if (!$type) {
                return;
            }
            $key = $type . $config->getId();

            unset(self::$cache[$key]);
        } else {
            self::$cache = [];
        }
    }

    /**
     * @param int[] $ids
     *
     * @return int[]
     */
    private static function filterHeldKeys(array $ids): array
    {
        return array_values(array_filter(
            array_unique($ids),
            static fn (int $id) => !isset(self::$cache['key' . $id])
        ));
    }

    /**
     * @param int[] $ids
     */
    private static function loadKeys(array $ids, bool $includeDisabled): void
    {
        $list = new KeyConfig\Listing();
        $list->setIncludeDisabled($includeDisabled);
        $list->setCondition('`id` in (' . implode(',', array_fill(0, count($ids), '?')) . ')', $ids);

        // hydrated exactly as KeyConfig\Dao::getById() does, so that a row with a null column
        // keeps the property default here as well
        foreach ($list->getDao()->getDataArray() as $row) {
            $row['enabled'] = (bool) $row['enabled'];

            $config = new KeyConfig();
            $config->setValues($row, true);

            self::put($config);
        }
    }

    protected static function getType(GroupConfig|KeyConfig $config): ?string
    {
        $type = null;

        if ($config instanceof KeyConfig) {
            $type = 'key';
        } elseif ($config instanceof GroupConfig) {
            $type = 'group';
        }

        return $type;
    }
}
