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
final class BooleanFormatter extends AbstractOperator
{
    private string $yesValue;

    private string $noValue;

    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->yesValue = $config->yesValue ?? '';
        $this->noValue = $config->noValue ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabeledValue(array|ElementInterface $element): ResultContainer|\stdClass|null
    {
        $result = new \stdClass();
        $result->label = $this->label;

        $children = $this->getChildren();

        $booleanResult = null;

        foreach ($children as $c) {
            $childResult = $c->getLabeledValue($element);
            $childValues = $childResult->value;
            if ($childValues && !is_array($childValues)) {
                $childValues = [$childValues];
            }

            if (is_array($childValues)) {
                foreach ($childValues as $value) {
                    $value = (bool) $value;
                    $booleanResult = is_null($booleanResult) ? $value : $booleanResult && $value;
                }
            } else {
                $booleanResult = false;
            }
        }

        $booleanResult = $booleanResult ? $this->getYesValue() : $this->getNoValue();
        $result->value = $booleanResult;

        return $result;
    }

    public function getYesValue(): mixed
    {
        return $this->yesValue;
    }

    public function setYesValue(mixed $yesValue): void
    {
        $this->yesValue = $yesValue;
    }

    public function getNoValue(): mixed
    {
        return $this->noValue;
    }

    public function setNoValue(mixed $noValue): void
    {
        $this->noValue = $noValue;
    }
}
