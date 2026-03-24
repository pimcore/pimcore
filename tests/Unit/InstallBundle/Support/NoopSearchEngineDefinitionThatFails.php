<?php
declare(strict_types=1);

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
