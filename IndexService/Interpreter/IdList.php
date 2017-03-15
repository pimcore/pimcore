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

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Interpreter;

class IdList implements IInterpreter {

    public static function interpret($value, $config = null) {
        $ids = [];

        if(is_array($value)) {
            foreach($value as $val) {
                if(method_exists($val, 'getId')) {
                    $ids[] = $val->getId();
                }
            }
        } elseif(method_exists($value, 'getId')) {
            $ids[] = $value->getId();
        }

        $delimiter = ',';

        if($config && $config->multiSelectEncoded) {
           $delimiter = \OnlineShop\Framework\IndexService\Worker\IWorker::MULTISELECT_DELIMITER;
        }

        $ids = implode($delimiter, $ids);

        return $ids ? $delimiter . $ids . $delimiter : null;
    }
}
