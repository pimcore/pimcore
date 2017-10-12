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

namespace Pimcore\Model\DataObject\GridConfig;

class Service
{
    /**
     * @param $outputDataConfig
     *
     * @return ConfigElementInterface[]
     */
    public static function buildOutputDataConfig($outputDataConfig, $context = null)
    {
        $config = [];
        $config = self::doBuildConfig($outputDataConfig, $config, $context);

        return $config;
    }

    private static function doBuildConfig($jsonConfig, $config, $context = null)
    {
        if (!empty($jsonConfig)) {
            foreach ($jsonConfig as $configElement) {
                if ($configElement->type == 'value') {
                    $name = 'Pimcore\\Model\\DataObject\\GridConfig\\Value\\' . ucfirst($configElement->class);

                    if (class_exists($name)) {
                        $config[] = new $name($configElement, $context);
                    }
                } elseif ($configElement->type == 'operator') {
                    $name = 'Pimcore\\Model\\DataObject\\GridConfig\\Operator\\' . ucfirst($configElement->class);

                    if (!empty($configElement->childs)) {
                        $configElement->childs = self::doBuildConfig($configElement->childs, [], $context);
                    }

                    if (class_exists($name)) {
                        $config[] = new $name($configElement, $context);
                    }
                }
            }
        }

        return $config;
    }
}
