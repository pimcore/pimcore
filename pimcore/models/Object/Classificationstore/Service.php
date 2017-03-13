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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\Classificationstore;

use Pimcore\Model\Object;

class Service
{

    /**
     * @param $keyConfig
     * @return Object\ClassDefinition\Data
     */
    public static function getFieldDefinitionFromKeyConfig($keyConfig)
    {
        $definition = $keyConfig->getDefinition();
        $definition = json_decode($definition, true);
        $type = $keyConfig->getType();
        $fd = self::getFieldDefinitionFromJson($definition, $type);

        return $fd;
    }

    /**
     * @param $definition
     * @param $type
     * @return Object\ClassDefinition\Data
     */
    public static function getFieldDefinitionFromJson($definition, $type)
    {
        if (!$definition) {
            return null;
        }

        if (!$type) {
            $type = "input";
        }
        $className = "\\Pimcore\\Model\\Object\\ClassDefinition\\Data\\" . ucfirst($type);
        /** @var  $dataDefinition \Pimcore\Model\Object\ClassDefinition\Data */
        $dataDefinition = new $className();

        $dataDefinition->setValues($definition);

        if (method_exists($className, "__set_state")) {
            $dataDefinition = $className::__set_state($dataDefinition);
        }

        return $dataDefinition;
    }
}
