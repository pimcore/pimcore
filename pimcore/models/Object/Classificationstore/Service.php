<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Object
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Object\Classificationstore;

use Pimcore\Model;
use Pimcore\Model\Object;
use Pimcore\Tool;

class Service {

    /**
     * @param $keyConfig
     * @return Object\ClassDefinition\Data
     */
    public static function getFieldDefinitionFromKeyConfig($keyConfig) {
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
        if (!$type) {
            $type = "input";
        }
        $className = "\\Pimcore\\Model\\Object\\ClassDefinition\\Data\\" . ucfirst($type);
        /** @var  $dataDefinition \Pimcore\Model\Object\ClassDefinition\Data */
        $dataDefinition = new $className();

        $dataDefinition->setValues($definition);
        return $dataDefinition;
    }


}
