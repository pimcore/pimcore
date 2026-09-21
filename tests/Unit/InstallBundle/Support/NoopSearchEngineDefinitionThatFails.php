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

namespace Pimcore\Tests\Unit\InstallBundle\Support;

use Pimcore\Bundle\InstallBundle\EnvVarDefinition\SearchEngineDefinitionInterface;

/**
 * Search engine definition that always fails validation.
 * Used to test skip-validation matching by short class name.
 *
 * @internal
 */
final readonly class NoopSearchEngineDefinitionThatFails implements SearchEngineDefinitionInterface
{
    public function getKey(): string
    {
        return 'failing-search-engine';
    }

    public function getLabel(): string
    {
        return 'Failing Search Engine';
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getSectionName(): string
    {
        return 'test';
    }

    public function getParameters(): array
    {
        return [];
    }

    public function resolveEnvVars(array $collectedValues): array
    {
        return ['FAILING_SEARCH_DSN' => 'noop://localhost'];
    }

    public function validate(array $collectedValues): array
    {
        return ['Search engine connection refused'];
    }
}
