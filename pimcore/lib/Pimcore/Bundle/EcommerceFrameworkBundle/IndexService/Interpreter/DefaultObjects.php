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

use Pimcore\Model\DataObject\AbstractObject;

class DefaultObjects implements IRelationInterpreter
{
    public function interpret($value, $config = null)
    {
        $result = [];

        if (is_array($value)) {
            foreach ($value as $v) {
                $result[] = ['dest' => $v->getId(), 'type' => 'object'];
            }
        } elseif ($value instanceof AbstractObject) {
            $result[] = ['dest' => $value->getId(), 'type' => 'object'];
        }

        return $result;
    }
}
