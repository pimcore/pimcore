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
use Pimcore\Tool\Serialize;

/**
 * @internal
 */
final class PHP extends AbstractOperator
{
    private string $mode;

    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->mode = $config->mode ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabeledValue(array|ElementInterface $element): ResultContainer|\stdClass|null
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $children = $this->getChildren();

        if (!$children) {
            return $result;
        } else {
            $c = $children[0];

            $valueArray = [];

            $childResult = $c->getLabeledValue($element);

            $childValues = $childResult->value ?? null;
            $isArrayType = is_array($childValues);

            if ($childValues && !is_array($childValues)) {
                $childValues = [$childValues];
            }

            if (is_array($childValues)) {
                foreach ($childValues as $childValue) {
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
        }

        if ($this->mode === 's') {
            $result->value = Serialize::serialize($result->value);
        } elseif ($this->mode === 'u') {
            $result->value = Serialize::unserialize($result->value);
        }

        return $result;
    }
}
