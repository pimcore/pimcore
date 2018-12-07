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

class Iterator extends AbstractOperator
{
    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);
    }

    public function getLabeledValue($elements)
    {
        $result = new \stdClass();
        $result->label = $this->label;
        $result->value = [];
        if (!is_array($elements)) {
            return $result;
        }

        $childs = $this->getChilds();

        if (!$childs) {
            return $result;
        } else {
            $c = $childs[0];

            $valueArray = [];

            foreach ($elements as $element) {
                $childResult = $c->getLabeledValue($element);

                $valueArray[] = $childResult->value ? $childResult->value : null;
            }

            $result->value = $valueArray;
        }

        return $result;
    }
}
