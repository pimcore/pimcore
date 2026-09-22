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
 * Lightweight search engine definition for tests that don't care about search.
 * Always passes validation and resolves to a dummy DSN.
 *
 * @internal
 */
final readonly class NoopSearchEngineDefinition implements SearchEngineDefinitionInterface
{
    public function getKey(): string
    {
        return 'noop-search-engine';
    }

    public function getLabel(): string
    {
        return 'Noop Search Engine';
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
        return ['NOOP_SEARCH_DSN' => 'noop://localhost'];
    }

    public function validate(array $collectedValues): array
    {
        return [];
    }
}
