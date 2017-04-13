<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Classificationstore;

class DefinitionCache
{
    /**
     * @var array
     */
    public static $cache = [];

    /**
     * @param $id
     * @param string $type
     *
     * @return mixed|KeyConfig
     */
    public static function get($id, $type = 'key')
    {
        $key = $type . $id;
        $config = self::$cache[$key];
        if ($config) {
            return $config;
        }

        $config = KeyConfig::getById($id);

        if (!$config) {
            return;
        }
        self::put($config);

        return $config;
    }

    /**
     * @param $config
     */
    public static function put($config)
    {
        $type = self::getType($config);
        if (!$type) {
            return;
        }
        $key = $type . $config->getId();
        self::$cache[$key] = $config;
    }

    /**
     * @param $config
     */
    public static function clear($config)
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
     * @param $config
     *
     * @return string
     */
    protected static function getType($config)
    {
        if ($config instanceof KeyConfig) {
            $type = 'key';
        } elseif ($config instanceof GroupConfig) {
            $type = 'group';
        }

        return $type;
    }
}
