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
final class Anonymizer extends AbstractOperator
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
        $result->isArrayType = true;

        $children = $this->getChildren();
        $resultItems = [];

        foreach ($children as $c) {
            $childResult = $c->getLabeledValue($element);
            $childValues = $childResult->value;

            if ($childValues) {
                if ($this->mode === 'md5') {
                    $childValues = md5($childValues);
                } elseif ($this->mode === 'bcrypt') {
                    $childValues = password_hash($childValues, PASSWORD_BCRYPT);
                }
                $resultItems[] = $childValues;
            } else {
                $resultItems[] = null;
            }
        }

        if (count($children) == 1) {
            $result->value = $resultItems[0];
        } else {
            $result->value = $resultItems;
        }

        return $result;
    }
}
