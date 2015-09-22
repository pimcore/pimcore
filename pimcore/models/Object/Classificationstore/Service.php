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
