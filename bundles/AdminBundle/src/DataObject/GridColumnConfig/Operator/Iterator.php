<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator;

use Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\ResultContainer;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
final class Iterator extends AbstractOperator
{
    /**
     * {@inheritdoc}
     */
    public function getLabeledValue(array|ElementInterface $element): ResultContainer|\stdClass|null
    {
        $result = new \stdClass();
        $result->label = $this->label;
        $result->value = [];
        if (!is_array($element)) {
            return $result;
        }

        $children = $this->getChildren();

        if (!$children) {
            return $result;
        } else {
            $c = $children[0];

            $valueArray = [];

            foreach ($element as $element) {
                $childResult = $c->getLabeledValue($element);

                $valueArray[] = $childResult->value ? $childResult->value : null;
            }

            $result->value = $valueArray;
        }

        return $result;
    }
}
