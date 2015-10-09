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


class OnlineShop_Framework_IndexService_Interpreter_Soundex implements OnlineShop_Framework_IndexService_Interpreter {

    public static function interpret($value, $config = null) {

        if(is_array($value)) {
            sort($value);
            $string = implode(" ", $value);
        } else {
            $string = (string)$value;
        }
        $soundex = soundex($string);
        return intval(ord(substr($soundex, 0, 1)) . substr($soundex, 1));
    }
}
