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

class ObjectIdSum implements IInterpreter {

    public static function interpret($value, $config = null) {

        $sum = 0;
        if(is_array($value)) {
            foreach($value as $object) {
                if($object instanceof Element_Interface) {
                    $sum += $object->getId();
                }
            }
        }
        return $sum;
    }
}
