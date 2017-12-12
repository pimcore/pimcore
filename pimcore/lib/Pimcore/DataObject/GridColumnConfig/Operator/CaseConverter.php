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

namespace Pimcore\DataObject\GridColumnConfig\Operator;

class CaseConverter extends AbstractOperator
{
    private $capitalization;

    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        $this->capitalization = $config->capitalization;
    }

    public function getLabeledValue($element)
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $childs = $this->getChilds();

        if (!$childs) {
            return $result;
        } else {
            $c = $childs[0];

            $valueArray = [];

            $childResult = $c->getLabeledValue($element);

            $childValues = $childResult->value;
            $isArrayType = is_array($childValues);

            if ($childValues && !is_array($childValues)) {
                $childValues = [$childValues];
            }

            /** @var $childValue string */
            if (is_array($childValues)) {
                foreach ($childValues as $childValue) {
                    if ($this->capitalization == 1) {
                        $childValue = strtoupper($childValue);
                    } elseif ($this->capitalization == -1) {
                        $childValue = strtolower($childValue);
                    }
                    $valueArray[] = $childValue;
                }
            } else {
                $valueArray[] = null;
            }

            if ($isArrayType) {
                $result->value = $valueArray;
            } else {
                $result->value = $valueArray[0];
            }
            $result->$valueArray;
        }

        return $result;
    }
}
