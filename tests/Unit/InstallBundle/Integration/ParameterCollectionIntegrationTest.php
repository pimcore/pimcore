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

namespace Pimcore\Tests\Unit\InstallBundle\Integration;

use Pimcore\Bundle\InstallBundle\Collector\ArrayEnvVarReader;
use Pimcore\Bundle\InstallBundle\Collector\ParameterCollector;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ConfigParameter;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\EnvVarDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterType;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Unit\InstallBundle\Support\InstallBundleTestHelperTrait;

/**
 * Integration tests for ParameterCollector with multiple definition types.
 *
 * Tests the collection priority chain (env var > interactive > default)
 * across different definition configurations: required, optional,
 * and no-param definitions.
 *
 * @internal
 */
final class ParameterCollectionIntegrationTest extends TestCase
{
    use InstallBundleTestHelperTrait;

    public function testEnvVarTakesPrecedenceOverDefault(): void
    {
        $envVarReader = new ArrayEnvVarReader();
        $envVarReader->set('DB_HOST', 'env-host');

        $definition = $this->createDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'default-host')],
            ['DB_HOST'],
        );

        $collector = new ParameterCollector($envVarReader);
        $result = $collector->collect($definition, $this->createNonInteractiveIo(), false);

        $this->assertNotNull($result);
        $this->assertSame('env-host', $result['DB_HOST']);
    }

    public function testDefaultValueUsedWhenNoEnvVar(): void
    {
        $envVarReader = new ArrayEnvVarReader();

        $definition = $this->createDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'default-host')],
            ['DB_HOST'],
        );

        $collector = new ParameterCollector($envVarReader);
        $result = $collector->collect($definition, $this->createNonInteractiveIo(), false);

        $this->assertNotNull($result);
        $this->assertSame('default-host', $result['DB_HOST']);
    }

    public function testEmptyStringReturnedWhenNoEnvVarAndNoDefault(): void
    {
        $envVarReader = new ArrayEnvVarReader();

        $definition = $this->createDefinition(
            'database',
            true,
            [new ConfigParameter('DB_HOST', 'Host', ParameterType::String)],
            ['DB_HOST'],
        );

        $collector = new ParameterCollector($envVarReader);
        $result = $collector->collect($definition, $this->createNonInteractiveIo(), false);

        $this->assertNotNull($result);
        $this->assertSame('', $result['DB_HOST']);
    }

    public function testCollectsMultipleParametersMixingEnvAndDefaults(): void
    {
        $envVarReader = new ArrayEnvVarReader();
        $envVarReader->set('DB_HOST', 'env-host');
        // DB_PORT not set — should fall back to default

        $definition = $this->createDefinition(
            'database',
            true,
            [
                new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'default-host'),
                new ConfigParameter('DB_PORT', 'Port', ParameterType::Integer, defaultValue: '3306'),
                new ConfigParameter('DB_NAME', 'Name', ParameterType::String, defaultValue: 'pimcore'),
            ],
            ['DB_HOST', 'DB_PORT', 'DB_NAME'],
        );

        $collector = new ParameterCollector($envVarReader);
        $result = $collector->collect($definition, $this->createNonInteractiveIo(), false);

        $this->assertNotNull($result);
        $this->assertSame('env-host', $result['DB_HOST']);
        $this->assertSame('3306', $result['DB_PORT']);
        $this->assertSame('pimcore', $result['DB_NAME']);
    }

    public function testOptionalDefinitionSkippedWhenNoEnvVarsAndNonInteractive(): void
    {
        $envVarReader = new ArrayEnvVarReader();

        $definition = $this->createDefinition(
            'redis',
            false,
            [new ConfigParameter('REDIS_URL', 'Redis URL', ParameterType::Url, defaultValue: 'redis://localhost')],
            ['REDIS_URL'],
        );

        $collector = new ParameterCollector($envVarReader);
        $result = $collector->collect($definition, $this->createNonInteractiveIo(), false);

        $this->assertNull($result, 'Optional definition should be skipped (null) when no env vars set non-interactively');
    }

    public function testOptionalDefinitionCollectedWhenEnvVarPresent(): void
    {
        $envVarReader = new ArrayEnvVarReader();
        $envVarReader->set('REDIS_URL', 'redis://custom-host:6380');

        $definition = $this->createDefinition(
            'redis',
            false,
            [new ConfigParameter('REDIS_URL', 'Redis URL', ParameterType::Url, defaultValue: 'redis://localhost')],
            ['REDIS_URL'],
        );

        $collector = new ParameterCollector($envVarReader);
        $result = $collector->collect($definition, $this->createNonInteractiveIo(), false);

        $this->assertNotNull($result);
        $this->assertSame('redis://custom-host:6380', $result['REDIS_URL']);
    }

    public function testDefinitionWithNoParametersReturnsEmptyArray(): void
    {
        $envVarReader = new ArrayEnvVarReader();

        $definition = $this->createDefinition('messenger', true, [], ['DSN']);
        $collector = new ParameterCollector($envVarReader);

        $result = $collector->collect($definition, $this->createNonInteractiveIo(), false);

        $this->assertNotNull($result);
        $this->assertSame([], $result);
    }

    public function testAggregatedCollectionAcrossMultipleDefinitions(): void
    {
        $envVarReader = new ArrayEnvVarReader();
        $envVarReader->set('DB_HOST', 'db-server');
        $envVarReader->set('REDIS_URL', 'redis://redis-server:6379');
        $envVarReader->set('SEARCH_DSN', 'opensearch://search:9200');

        $definitions = [
            $this->createDefinition(
                'database',
                true,
                [new ConfigParameter('DB_HOST', 'Host', ParameterType::String, defaultValue: 'localhost')],
                ['DB_HOST'],
            ),
            $this->createDefinition(
                'redis',
                false,
                [new ConfigParameter('REDIS_URL', 'URL', ParameterType::Url, defaultValue: 'redis://localhost')],
                ['REDIS_URL'],
            ),
            $this->createDefinition(
                'search',
                true,
                [new ConfigParameter('SEARCH_DSN', 'DSN', ParameterType::Url, defaultValue: 'opensearch://localhost')],
                ['SEARCH_DSN'],
            ),
        ];

        $collector = new ParameterCollector($envVarReader);
        $io = $this->createNonInteractiveIo();
        $allResults = [];

        foreach ($definitions as $def) {
            $result = $collector->collect($def, $io, false);
            if ($result !== null) {
                $allResults[$def->getKey()] = $result;
            }
        }

        $this->assertCount(3, $allResults);
        $this->assertSame('db-server', $allResults['database']['DB_HOST']);
        $this->assertSame('redis://redis-server:6379', $allResults['redis']['REDIS_URL']);
        $this->assertSame('opensearch://search:9200', $allResults['search']['SEARCH_DSN']);
    }

    /**
     * Creates a simple definition with given parameters and resolved env var names.
     *
     * @param list<ConfigParameter> $parameters
     * @param list<string> $resolvedEnvVarNames
     */
    private function createDefinition(
        string $key,
        bool $required,
        array $parameters,
        array $resolvedEnvVarNames,
    ): EnvVarDefinitionInterface {
        return new class($key, $required, $parameters, $resolvedEnvVarNames) implements EnvVarDefinitionInterface {
            public function __construct(
                private readonly string $key,
                private readonly bool $required,
                private readonly array $parameters,
                private readonly array $resolvedEnvVarNames,
            ) {
            }

            public function getKey(): string
            {
                return $this->key;
            }

            public function getLabel(): string
            {
                return ucfirst($this->key);
            }

            public function isRequired(): bool
            {
                return $this->required;
            }

            public function getSectionName(): string
            {
                return 'test';
            }

            public function getParameters(): array
            {
                return $this->parameters;
            }

            public function resolveEnvVars(array $collectedValues): array
            {
                $result = [];
                foreach ($this->resolvedEnvVarNames as $name) {
                    $result[$name] = $collectedValues[$name] ?? '';
                }

                return $result;
            }

            public function validate(array $collectedValues): array
            {
                return [];
            }
        };
    }
}
