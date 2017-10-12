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

namespace Pimcore\Model\DataObject\GridConfig\Operator;

class Trimmer extends AbstractOperator
{
    const LEFT = 1;
    const RIGHT = 2;
    const BOTH  = 3;
    protected $trim;

    public function __construct($config, $context = null)
    {
        parent::__construct($config, $context);
        $this->trim = $config->trim;
    }

    public function getLabeledValue($object)
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $childs = $this->getChilds();

        if (!$childs) {
            return $result;
        } else {
            $c = $childs[0];

            $valueArray = [];

            $childResult = $c->getLabeledValue($object);
            $isArrayType = $childResult->isArrayType;
            $childValues = $childResult->value;
            if ($childValues && !$isArrayType) {
                $childValues = [$childValues];
            }

            /** @var $childValue string */
            foreach ($childValues as $childValue) {
                if ($this->trim == self::LEFT) {
                    $childValue = ltrim($childValue);
                } elseif ($this->trim == self::RIGHT) {
                    $childValue = rtrim($childValue);
                } elseif ($this->trim == self::BOTH) {
                    $childValue = trim($childValue);
                }
                $valueArray[] = $childValue;
            }

            $result->isArrayType = $isArrayType;
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
