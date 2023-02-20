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

namespace Pimcore\Bundle\PersonalizationBundle\Targeting\Condition;

abstract class AbstractVariableCondition implements ConditionInterface, VariableConditionInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $variables = [];

    /**
     * {@inheritdoc}
     */
    public function getMatchedVariables(): array
    {
        return $this->variables;
    }

    final protected function setMatchedVariables(array $variables): void
    {
        $this->variables = $variables;
    }

    final protected function setMatchedVariable(string $key, mixed $value): void
    {
        $this->variables[$key] = $value;
    }
}
