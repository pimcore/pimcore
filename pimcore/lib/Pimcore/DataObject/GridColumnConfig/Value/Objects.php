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

use Pimcore\DataObject\GridColumnConfig\AbstractConfigElement;

class Objects extends AbstractConfigElement implements ValueInterface
{
    public function getLabeledValue($element)
    {
        $result = new \stdClass();
        $result->label = $this->label;
        $result->isArrayType = true;

        $getter = 'get' . ucfirst($this->attribute);
        if (method_exists($element, $getter)) {
            $result->value = $element->$getter();

            return $result;
        }

        return $result;
    }
}
