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
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Interpreter;

use Pimcore\Model\Object\Data\ObjectMetadata;

class DefaultRelations implements IRelationInterpreter {

    public static function interpret($value, $config = null) {
        $result = array();

        if($value instanceof ObjectMetadata) {
            $value = $value->getObject();
        }

        if(is_array($value)) {
            foreach($value as $v) {

                if($v instanceof ObjectMetadata) {
                    $v = $v->getObject();
                }

                $result[] = array("dest" => $v->getId(), "type" => \Pimcore\Model\Element\Service::getElementType($v));
            }
        } else if($value instanceof \Pimcore\Model\Element\AbstractElement) {
            $result[] = array("dest" => $value->getId(), "type" => \Pimcore\Model\Element\Service::getElementType($value));
        }
        return $result;
    }
}
