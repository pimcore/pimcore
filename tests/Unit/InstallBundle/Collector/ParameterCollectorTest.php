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

namespace Pimcore\Tests\Unit\InstallBundle\Collector;

use Pimcore\Bundle\InstallBundle\Collector\ArrayEnvVarReader;
use Pimcore\Bundle\InstallBundle\Collector\ParameterCollector;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ConfigParameter;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\EnvVarDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterType;
use Pimcore\Tests\Support\Test\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ParameterCollectorTest extends TestCase
{
    private ArrayEnvVarReader $envVarReader;

    private ParameterCollector $collector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->envVarReader = new ArrayEnvVarReader();
        $this->collector = new ParameterCollector($this->envVarReader);
    }

    public function testCollectFromEnvVars(): void
    {
        $this->envVarReader->set('TEST_VAR', 'from-env');

        $definition = $this->createSimpleDefinition(
            'test',
            true,
            [new ConfigParameter('TEST_VAR', 'Test', ParameterType::String)],
        );

        $io = $this->createNonInteractiveIo();
        $values = $this->collector->collect($definition, $io, false);

        $this->assertSame(['TEST_VAR' => 'from-env'], $values);
    }

    public function testCollectUsesDefaultForNonInteractive(): void
    {
        $definition = $this->createSimpleDefinition(
            'test',
            true,
            [new ConfigParameter(
                'TEST_VAR',
                'Test',
                ParameterType::String,
                defaultValue: 'default-val',
            )],
        );

        $io = $this->createNonInteractiveIo();
        $values = $this->collector->collect($definition, $io, false);

        $this->assertSame(['TEST_VAR' => 'default-val'], $values);
    }

    public function testOptionalDefinitionSkippedWhenNoEnvVarsAndNonInteractive(): void
    {
        $definition = $this->createSimpleDefinition(
            'redis',
            false,
            [new ConfigParameter(
                'REDIS_URL',
                'Redis URL',
                ParameterType::Url,
                defaultValue: 'redis://127.0.0.1:6379',
            )],
        );

        $io = $this->createNonInteractiveIo();
        $values = $this->collector->collect($definition, $io, false);

        $this->assertNull($values);
    }

    public function testOptionalDefinitionCollectedWhenEnvVarPresent(): void
    {
        $this->envVarReader->set('REDIS_URL', 'redis://redis:6379');

        $definition = $this->createSimpleDefinition(
            'redis',
            false,
            [new ConfigParameter('REDIS_URL', 'Redis URL', ParameterType::Url)],
        );

        $io = $this->createNonInteractiveIo();
        $values = $this->collector->collect($definition, $io, false);

        $this->assertSame(['REDIS_URL' => 'redis://redis:6379'], $values);
    }

    public function testSkipsTransientParamsWhenFinalDsnIsSet(): void
    {
        $this->envVarReader->set('DATABASE_URL', 'mysql://root:pass@db:3306/pimcore');

        $definition = $this->createDatabaseLikeDefinition();

        $io = $this->createNonInteractiveIo();
        $values = $this->collector->collect($definition, $io, false);

        // Should contain the final DSN, not the transient parts
        $this->assertArrayHasKey('DATABASE_URL', $values);
        $this->assertSame(
            'mysql://root:pass@db:3306/pimcore',
            $values['DATABASE_URL'],
        );
        // Transient params should NOT be in collected values
        $this->assertArrayNotHasKey('DATABASE_HOST', $values);
        $this->assertArrayNotHasKey('DATABASE_PORT', $values);
        $this->assertArrayNotHasKey('DATABASE_NAME', $values);
        $this->assertArrayNotHasKey('DATABASE_USER', $values);
        $this->assertArrayNotHasKey('DATABASE_PASSWORD', $values);
    }

    public function testCollectsTransientParamsWhenFinalDsnNotSet(): void
    {
        $definition = $this->createDatabaseLikeDefinition();

        $io = $this->createNonInteractiveIo();
        $values = $this->collector->collect($definition, $io, false);

        // When DATABASE_URL is not set, transient params should be collected
        // with their defaults (non-interactive mode)
        $this->assertArrayHasKey('DATABASE_HOST', $values);
        $this->assertSame('127.0.0.1', $values['DATABASE_HOST']);
        $this->assertArrayHasKey('DATABASE_PORT', $values);
        $this->assertSame('3306', $values['DATABASE_PORT']);
        $this->assertArrayHasKey('DATABASE_NAME', $values);
        $this->assertSame('pimcore', $values['DATABASE_NAME']);
        $this->assertArrayHasKey('DATABASE_USER', $values);
        $this->assertSame('root', $values['DATABASE_USER']);
        // DATABASE_PASSWORD has no default and is not required, so empty string
        $this->assertArrayHasKey('DATABASE_PASSWORD', $values);
        $this->assertSame('', $values['DATABASE_PASSWORD']);
    }

    public function testEnvVarPriorityOverDefault(): void
    {
        $this->envVarReader->set('TEST_VAR', 'env-value');

        $definition = $this->createSimpleDefinition(
            'test',
            true,
            [new ConfigParameter(
                'TEST_VAR',
                'Test',
                ParameterType::String,
                defaultValue: 'default-value',
            )],
        );

        $io = $this->createNonInteractiveIo();
        $values = $this->collector->collect($definition, $io, false);

        // Env var should take priority over default
        $this->assertSame(['TEST_VAR' => 'env-value'], $values);
    }

    public function testRequiredParameterWithNoDefaultReturnsEmptyString(): void
    {
        $definition = $this->createSimpleDefinition(
            'test',
            true,
            [new ConfigParameter('TEST_VAR', 'Test', ParameterType::String)],
        );

        $io = $this->createNonInteractiveIo();
        $values = $this->collector->collect($definition, $io, false);

        // No env var, no default, non-interactive: returns empty string
        // The definition's validate() will catch this
        $this->assertSame(['TEST_VAR' => ''], $values);
    }

    public function testRequiredDefinitionAlwaysCollected(): void
    {
        // Required definitions should always be collected,
        // even when no env vars are set (non-interactive uses defaults)
        $definition = $this->createSimpleDefinition(
            'database',
            true,
            [new ConfigParameter(
                'DB_HOST',
                'Database Host',
                ParameterType::String,
                defaultValue: 'localhost',
            )],
        );

        $io = $this->createNonInteractiveIo();
        $values = $this->collector->collect($definition, $io, false);

        $this->assertNotNull($values);
        $this->assertSame(['DB_HOST' => 'localhost'], $values);
    }

    public function testMultipleParametersCollected(): void
    {
        $this->envVarReader->set('HOST', 'db.example.com');

        $definition = $this->createSimpleDefinition(
            'test',
            true,
            [
                new ConfigParameter(
                    'HOST',
                    'Host',
                    ParameterType::String,
                    defaultValue: 'localhost',
                ),
                new ConfigParameter(
                    'PORT',
                    'Port',
                    ParameterType::Integer,
                    defaultValue: '5432',
                ),
            ],
        );

        $io = $this->createNonInteractiveIo();
        $values = $this->collector->collect($definition, $io, false);

        // HOST from env, PORT from default
        $this->assertSame('db.example.com', $values['HOST']);
        $this->assertSame('5432', $values['PORT']);
    }

    public function testOptionalDefinitionCollectedWhenResolvedEnvVarPresent(): void
    {
        // The final DSN env var is set (not a transient param), so the
        // optional definition should be collected
        $this->envVarReader->set('DATABASE_URL', 'mysql://root@localhost/pimcore');

        $definition = $this->createOptionalDatabaseDefinition();

        $io = $this->createNonInteractiveIo();
        $values = $this->collector->collect($definition, $io, false);

        $this->assertNotNull($values);
        $this->assertArrayHasKey('DATABASE_URL', $values);
    }

    private function createSimpleDefinition(
        string $key,
        bool $required,
        array $parameters,
    ): EnvVarDefinitionInterface {
        return new class($key, $required, $parameters) implements EnvVarDefinitionInterface {
            public function __construct(
                private readonly string $key,
                private readonly bool $required,
                private readonly array $parameters,
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
                foreach ($this->parameters as $param) {
                    if (!$param->transient) {
                        $result[$param->envVarName] =
                            $collectedValues[$param->envVarName] ?? '';
                    }
                }

                return $result;
            }

            public function validate(array $collectedValues): array
            {
                return [];
            }
        };
    }

    private function createDatabaseLikeDefinition(): EnvVarDefinitionInterface
    {
        return new class() implements EnvVarDefinitionInterface {
            public function getKey(): string
            {
                return 'database';
            }

            public function getLabel(): string
            {
                return 'Database';
            }

            public function isRequired(): bool
            {
                return true;
            }

            public function getSectionName(): string
            {
                return 'pimcore/pimcore';
            }

            public function getParameters(): array
            {
                return [
                    new ConfigParameter(
                        'DATABASE_HOST',
                        'Host',
                        ParameterType::String,
                        defaultValue: '127.0.0.1',
                        transient: true,
                    ),
                    new ConfigParameter(
                        'DATABASE_PORT',
                        'Port',
                        ParameterType::Integer,
                        defaultValue: '3306',
                        transient: true,
                    ),
                    new ConfigParameter(
                        'DATABASE_NAME',
                        'DB',
                        ParameterType::String,
                        defaultValue: 'pimcore',
                        transient: true,
                    ),
                    new ConfigParameter(
                        'DATABASE_USER',
                        'User',
                        ParameterType::String,
                        defaultValue: 'root',
                        transient: true,
                    ),
                    new ConfigParameter(
                        'DATABASE_PASSWORD',
                        'Pass',
                        ParameterType::Secret,
                        required: false,
                        transient: true,
                    ),
                ];
            }

            public function resolveEnvVars(array $collectedValues): array
            {
                return ['DATABASE_URL' => sprintf(
                    'mysql://%s:%s@%s:%s/%s',
                    $collectedValues['DATABASE_USER'] ?? 'root',
                    $collectedValues['DATABASE_PASSWORD'] ?? '',
                    $collectedValues['DATABASE_HOST'] ?? '127.0.0.1',
                    $collectedValues['DATABASE_PORT'] ?? '3306',
                    $collectedValues['DATABASE_NAME'] ?? 'pimcore',
                )];
            }

            public function validate(array $collectedValues): array
            {
                return [];
            }
        };
    }

    private function createOptionalDatabaseDefinition(): EnvVarDefinitionInterface
    {
        return new class() implements EnvVarDefinitionInterface {
            public function getKey(): string
            {
                return 'optional-db';
            }

            public function getLabel(): string
            {
                return 'Optional Database';
            }

            public function isRequired(): bool
            {
                return false;
            }

            public function getSectionName(): string
            {
                return 'test';
            }

            public function getParameters(): array
            {
                return [
                    new ConfigParameter(
                        'DATABASE_HOST',
                        'Host',
                        ParameterType::String,
                        defaultValue: 'localhost',
                        transient: true,
                    ),
                ];
            }

            public function resolveEnvVars(array $collectedValues): array
            {
                return ['DATABASE_URL' => sprintf(
                    'mysql://%s',
                    $collectedValues['DATABASE_HOST'] ?? 'localhost',
                )];
            }

            public function validate(array $collectedValues): array
            {
                return [];
            }
        };
    }

    private function createNonInteractiveIo(): SymfonyStyle
    {
        return new SymfonyStyle(
            new ArrayInput([]),
            new NullOutput(),
        );
    }
}
