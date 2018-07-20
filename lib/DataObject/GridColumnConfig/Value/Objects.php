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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DataObject\GridColumnConfig\Value;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Service;

class Objects extends AbstractValue
{
    private function getValue($element)
    {
        $getter = 'get' . ucfirst($this->attribute);

        if (method_exists($element, $getter)) {
            $value = $element->$getter();

            if (
                $element instanceof Concrete &&
                !$value &&
                ($parent = Service::hasInheritableParentObject($element))
            ) {
                $value = $this->getValue($parent);
            }

            return $value;
        }

        return null;
    }

    public function getLabeledValue($element)
    {
        $result = new \stdClass();
        $result->label = $this->label;
        $result->isArrayType = true;

        $result->value = $this->getValue($element);

        return $result;
    }
}
