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
