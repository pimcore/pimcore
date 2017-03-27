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

use Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Worker\IWorker;

class IdList implements IInterpreter
{
    public static function interpret($value, $config = null)
    {
        $ids = [];

        if (is_array($value)) {
            foreach ($value as $val) {
                if (method_exists($val, 'getId')) {
                    $ids[] = $val->getId();
                }
            }
        } elseif (method_exists($value, 'getId')) {
            $ids[] = $value->getId();
        }

        $delimiter = ',';

        if ($config && $config->multiSelectEncoded) {
            $delimiter = IWorker::MULTISELECT_DELIMITER;
        }

        $ids = implode($delimiter, $ids);

        return $ids ? $delimiter . $ids . $delimiter : null;
    }
}
