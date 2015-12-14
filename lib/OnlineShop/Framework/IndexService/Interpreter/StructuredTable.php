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

class StructuredTable implements IInterpreter {

    public static function interpret($value, $config = null) {

        if(empty($config->tablerow)) {
            throw new \Exception("Table row config missing.");
        }
        if(empty($config->tablecolumn)) {
            throw new \Exception("Table column config missing.");
        }

        $getter = "get" . ucfirst($config->tablerow) . "__" . ucfirst($config->tablecolumn);

        if($value && $value instanceof \Pimcore\Model\Object\Data\StructuredTable) {
            if(!empty($config->defaultUnit)) {
                return $value->$getter() . " " . $config->defaultUnit;
            } else {
                return $value->$getter();
            }
        }

        return null;
    }
}
