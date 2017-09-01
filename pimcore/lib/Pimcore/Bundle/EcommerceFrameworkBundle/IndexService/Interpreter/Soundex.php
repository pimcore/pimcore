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

class Soundex implements IInterpreter
{
    public function interpret($value, $config = null)
    {
        if (is_array($value)) {
            sort($value);
            $string = implode(' ', $value);
        } else {
            $string = (string)$value;
        }
        $soundex = soundex($string);

        return intval(ord(substr($soundex, 0, 1)) . substr($soundex, 1));
    }
}
