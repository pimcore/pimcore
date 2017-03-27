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

class StructuredTable implements IInterpreter
{
    public static function interpret($value, $config = null)
    {
        if (empty($config->tablerow)) {
            throw new \Exception("Table row config missing.");
        }
        if (empty($config->tablecolumn)) {
            throw new \Exception("Table column config missing.");
        }

        $getter = "get" . ucfirst($config->tablerow) . "__" . ucfirst($config->tablecolumn);

        if ($value && $value instanceof \Pimcore\Model\Object\Data\StructuredTable) {
            if (!empty($config->defaultUnit)) {
                return $value->$getter() . " " . $config->defaultUnit;
            } else {
                return $value->$getter();
            }
        }

        return null;
    }
}
