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
final class ElementCounter extends AbstractOperator
{
    private bool $countEmpty;

    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->countEmpty = $config->countEmpty ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabeledValue(array|ElementInterface $element): ResultContainer|\stdClass|null
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $children = $this->getChildren();
        $count = 0;

        foreach ($children as $c) {
            $childResult = $c->getLabeledValue($element);
            $childValues = $childResult->value ?? null;

            if ($this->getCountEmpty()) {
                if (is_array($childValues)) {
                    $count += count($childValues);
                } else {
                    $count++;
                }
            } else {
                if (is_array($childValues)) {
                    foreach ($childValues as $childValue) {
                        if ($childValue) {
                            $count++;
                        }
                    }
                } elseif ($childValues) {
                    $count++;
                }
            }
        }

        $result->value = $count;

        return $result;
    }

    public function getCountEmpty(): bool
    {
        return $this->countEmpty;
    }

    public function setCountEmpty(bool $countEmpty): void
    {
        $this->countEmpty = $countEmpty;
    }
}
