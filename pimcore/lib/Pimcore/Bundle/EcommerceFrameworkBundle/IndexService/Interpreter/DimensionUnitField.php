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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter;

class DimensionUnitField implements IInterpreter
{
    public static function interpret($value, $config = null)
    {
        if (!empty($value) && $value instanceof \Object_Data_DimensionUnitField) {
            if ($config->onlyDimensionValue == "true") {
                $unit = $value->getUnit();
                $value = $value->getValue();

                if ($unit->getFactor()) {
                    $value *= $unit->getFactor();
                }

                return $value;
            } else {
                return $value->__toString();
            }
        }

        return null;
    }
}
