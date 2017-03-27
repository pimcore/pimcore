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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Interpreter;

class DefaultObjects implements IRelationInterpreter
{
    public static function interpret($value, $config = null)
    {
        $result = [];

        if (is_array($value)) {
            foreach ($value as $v) {
                $result[] = ["dest" => $v->getId(), "type" => "object"];
            }
        } elseif ($value instanceof \Pimcore\Model\Object\AbstractObject) {
            $result[] = ["dest" => $value->getId(), "type" => "object"];
        }

        return $result;
    }
}
