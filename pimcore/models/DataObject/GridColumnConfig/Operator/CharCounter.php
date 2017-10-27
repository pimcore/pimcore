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

namespace Pimcore\Model\DataObject\GridColumnConfig\Operator;

class CharCounter extends AbstractOperator
{
    public function __construct($config, $context = null)
    {
        parent::__construct($config, $context);
    }

    public function getLabeledValue($element)
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $childs = $this->getChilds();
        $count = 0;

        foreach ($childs as $c) {
            $childResult = $c->getLabeledValue($element);
            $isArrayType = $childResult->isArrayType;
            $childValues = $childResult->value;
            if ($childValues && !$isArrayType) {
                $childValues = [$childValues];
            }

            if (is_array($childValues)) {
                foreach ($childValues as $value) {
                    if (is_array($value)) {
                        foreach ($value as $subValue) {
                            $count += strlen($subValue);
                        }
                    } else {
                        $count += strlen($value);
                    }
                }
            }
        }

        $result->value = $count;

        return $result;
    }
}
