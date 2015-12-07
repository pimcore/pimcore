<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace OnlineShop\Framework\IndexService\Interpreter;

class DimensionUnitField implements IInterpreter {

    public static function interpret($value, $config = null) {

        if(!empty($value) && $value instanceof \Object_Data_DimensionUnitField) {

            if($config->onlyDimensionValue == "true") {
                $unit = $value->getUnit();
                $value = $value->getValue();

                if($unit->getFactor()) {
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
