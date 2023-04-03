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
final class Substring extends AbstractOperator
{
    private int $start;

    private int $length;

    private bool $ellipses;

    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->start = $config->start ?? 0;
        $this->length = $config->length ?? 0;
        $this->ellipses = $config->ellipses ?? false;
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
            $isArrayType = $childResult->isArrayType ?? false;
            $childValues = $childResult->value ?? null;
            if ($childValues && !$isArrayType) {
                $childValues = [$childValues];
            }

            if (is_array($childValues)) {
                /** @var string $childValue */
                foreach ($childValues as $childValue) {
                    $showEllipses = false;
                    if ($childValue && $this->getEllipses()) {
                        $start = $this->getStart() ? $this->getStart() : 0;
                        $length = $this->getLength() ? $this->getLength() : 0;
                        if (strlen($childValue) > ($start + $length)) {
                            $showEllipses = true;
                        }
                    }

                    $childValue = substr($childValue, $this->getStart(), $this->getLength());
                    if ($showEllipses) {
                        $childValue .= '...';
                    }

                    $valueArray[] = $childValue;
                }
            } else {
                $valueArray[] = $childResult->value;
            }

            $result->isArrayType = $isArrayType;
            if ($isArrayType) {
                $result->value = $valueArray;
            } else {
                $result->value = $valueArray[0];
            }
        }

        return $result;
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function setStart(int $start): void
    {
        $this->start = $start;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function setLength(int $length): void
    {
        $this->length = $length;
    }

    public function getEllipses(): bool
    {
        return $this->ellipses;
    }

    public function setEllipses(bool $ellipses): void
    {
        $this->ellipses = $ellipses;
    }
}
