<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Object\Classificationstore;

use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Tool;

class DefinitionCache {

    static $cache = array();

    public static function get($id, $type = "key") {
        $key = $type . $id;
        $config = self::$cache[$key];
        if ($config) {
            \Logger::debug("#### matched " . $key);
            return $config;
        }

        $config = KeyConfig::getById($id);
        if (!$config->getId()) {
            return;
        }
        self::put($config);
        return $config;

    }

    public static function put($config)
    {
        $type = self::getType($config);
        if (!$type) {
            return;
        }
        $key = $type . $config->getId();
        self::$cache[$key] = $config;
    }

    public static function clear($config) {

        if ($config) {
            $type = self::getType($config);
            if (!$type) {
                return;
            }
            $key = $type . $config->getId();

            unset(self::$cache[$key]);
        } else {
            self::$cache = array();
        }

    }

    protected static function getType($config) {
        if ($config instanceof KeyConfig) {
            $type = "key";
        } else if ($config instanceof GroupConfig) {
            $type = "group";
        }
        return $type;
    }


}
