<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\InstallBundle\EnvVarDefinition;

/**
 * Pairs a validated definition with its collected parameter values.
 *
 * @internal
 */
final readonly class ResolvedDefinition
{
    /**
     * @param array<string, string> $values
     */
    public function __construct(
        private EnvVarDefinitionInterface $definition,
        private array $values,
    ) {
    }

    public function getDefinition(): EnvVarDefinitionInterface
    {
        return $this->definition;
    }

    /**
     * @return array<string, string>
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
