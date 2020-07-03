<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Targeting\ConditionMatcher;

class ExpressionBuilder
{
    /**
     * @var array
     */
    private $parts = [];

    /**
     * @var array
     */
    private $values = [];

    /**
     * @var int
     */
    private $valueIndex = 1;

    public function getExpression(): string
    {
        return implode('', $this->parts);
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function addCondition(array $config, bool $result)
    {
        if (!empty($this->parts)) {
            $this->parts[] = $this->normalizeOperator($config['operator']);
        }

        if ($config['bracketLeft']) {
            $this->parts[] = ' (';
        }

        $valueKey = $config['type'] . '_' . $this->valueIndex++;

        $this->values[$valueKey] = $result;
        $this->parts[] = $valueKey;

        if ($config['bracketRight']) {
            $this->parts[] = ') ';
        }
    }

    private function normalizeOperator(string $operator = null): string
    {
        if (empty($operator)) {
            $operator = 'and';
        }

        $mapping = [
            'and_not' => 'and not',
        ];

        if (isset($mapping[$operator])) {
            $operator = $mapping[$operator];
        }

        $operator = sprintf(' %s ', $operator);

        return $operator;
    }
}
