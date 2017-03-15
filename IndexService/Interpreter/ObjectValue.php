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
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\IndexService\Interpreter;

class ObjectValue implements IInterpreter
{

    public static function interpret($value, $config = null)
    {
        $targetList = $config->target;

        if (empty($targetList->fieldname)) {
            throw new \Exception("target fieldname missing.");
        }

        if ($value instanceof \Pimcore\Model\Object\AbstractObject) {

            $fieldGetter = "get" . ucfirst($targetList->fieldname);

            if (method_exists($value, $fieldGetter)) {
                return $value->$fieldGetter($targetList->locale);
            }
        }
        return null;
    }
}