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
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterHintProviderInterface;
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

    public function testInteractiveWithEnvVarUsesEnvVarAsDefault(): void
    {
        $this->envVarReader->set('TEST_VAR', 'from-env');

        $definition = $this->createSimpleDefinition(
            'test',
            true,
            [new ConfigParameter(
                'TEST_VAR',
                'Test Variable',
                ParameterType::String,
                defaultValue: 'default-val',
            )],
        );

        // Simulate user pressing Enter (accepts the default/suggestion)
        $io = $this->createInteractiveIo("\n");
        $values = $this->collector->collect($definition, $io, true);

        // The env var value should be used as the suggestion, so pressing
        // Enter should yield the env var value, NOT the ConfigParameter default
        $this->assertSame(['TEST_VAR' => 'from-env'], $values);
    }

    public function testInteractiveWithoutEnvVarUsesParameterDefault(): void
    {
        $definition = $this->createSimpleDefinition(
            'test',
            true,
            [new ConfigParameter(
                'TEST_VAR',
                'Test Variable',
                ParameterType::String,
                defaultValue: 'default-val',
            )],
        );

        // Simulate user pressing Enter (accepts the default)
        $io = $this->createInteractiveIo("\n");
        $values = $this->collector->collect($definition, $io, true);

        // No env var → ConfigParameter default used as suggestion
        $this->assertSame(['TEST_VAR' => 'default-val'], $values);
    }

    public function testInteractiveUserCanOverrideEnvVar(): void
    {
        $this->envVarReader->set('TEST_VAR', 'from-env');

        $definition = $this->createSimpleDefinition(
            'test',
            true,
            [new ConfigParameter(
                'TEST_VAR',
                'Test Variable',
                ParameterType::String,
                defaultValue: 'default-val',
            )],
        );

        // Simulate user typing a custom value
        $io = $this->createInteractiveIo("custom-value\n");
        $values = $this->collector->collect($definition, $io, true);

        $this->assertSame(['TEST_VAR' => 'custom-value'], $values);
    }

    public function testInteractiveSecretWithEnvVarKeepsOnEnter(): void
    {
        $this->envVarReader->set('MY_SECRET', 's3cret');

        $definition = $this->createSimpleDefinition(
            'test',
            true,
            [new ConfigParameter(
                'MY_SECRET',
                'Secret Key',
                ParameterType::Secret,
            )],
        );

        // Simulate user pressing Enter (hidden input returns null/empty)
        $io = $this->createInteractiveIo("\n");
        $values = $this->collector->collect($definition, $io, true);

        // Empty input on a pre-configured secret → keeps the existing env var value
        $this->assertSame(['MY_SECRET' => 's3cret'], $values);
    }

    public function testInteractiveSecretWithEnvVarReplacesOnInput(): void
    {
        $this->envVarReader->set('MY_SECRET', 'old-secret');

        $definition = $this->createSimpleDefinition(
            'test',
            true,
            [new ConfigParameter(
                'MY_SECRET',
                'Secret Key',
                ParameterType::Secret,
            )],
        );

        // Simulate user typing a new secret
        $io = $this->createInteractiveIo("new-secret\n");
        $values = $this->collector->collect($definition, $io, true);

        $this->assertSame(['MY_SECRET' => 'new-secret'], $values);
    }

    public function testInteractiveSecretWithoutEnvVarAcceptsInput(): void
    {
        $definition = $this->createSimpleDefinition(
            'test',
            true,
            [new ConfigParameter(
                'MY_SECRET',
                'Secret Key',
                ParameterType::Secret,
            )],
        );

        // Simulate user typing a value
        $io = $this->createInteractiveIo("brand-new-secret\n");
        $values = $this->collector->collect($definition, $io, true);

        $this->assertSame(['MY_SECRET' => 'brand-new-secret'], $values);
    }

    public function testInteractiveBooleanWithEnvVarUsesEnvVarAsDefault(): void
    {
        $this->envVarReader->set('ENABLE_FEATURE', 'true');

        $definition = $this->createSimpleDefinition(
            'test',
            true,
            [new ConfigParameter(
                'ENABLE_FEATURE',
                'Enable feature?',
                ParameterType::Boolean,
                defaultValue: 'false',
            )],
        );

        // Simulate user pressing Enter to accept the default (yes, from env var 'true')
        $io = $this->createInteractiveIo("\n");
        $values = $this->collector->collect($definition, $io, true);

        // Env var is 'true' so the confirm should default to yes
        $this->assertSame(['ENABLE_FEATURE' => 'true'], $values);
    }

    public function testInteractiveChoiceWithEnvVarUsesEnvVarAsSuggestion(): void
    {
        $this->envVarReader->set('DB_DRIVER', 'pgsql');

        $definition = $this->createSimpleDefinition(
            'test',
            true,
            [new ConfigParameter(
                'DB_DRIVER',
                'Database Driver',
                ParameterType::Choice,
                defaultValue: 'mysql',
                choices: ['mysql', 'pgsql', 'sqlite'],
            )],
        );

        // Simulate user pressing Enter to accept the pre-selected choice
        $io = $this->createInteractiveIo("\n");
        $values = $this->collector->collect($definition, $io, true);

        // Env var 'pgsql' should be the pre-selected suggestion
        $this->assertSame(['DB_DRIVER' => 'pgsql'], $values);
    }

    public function testInteractiveOptionalDefinitionShowsGatePrompt(): void
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

        // Simulate user answering "no" to the gate prompt
        $io = $this->createInteractiveIo("no\n");
        $values = $this->collector->collect($definition, $io, true);

        // User declined the optional definition
        $this->assertNull($values);
    }

    public function testInteractiveOptionalDefinitionAccepted(): void
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

        // Simulate: "yes" to gate, then Enter to accept default URL
        $io = $this->createInteractiveIo("yes\n\n");
        $values = $this->collector->collect($definition, $io, true);

        $this->assertSame(['REDIS_URL' => 'redis://127.0.0.1:6379'], $values);
    }

    public function testInteractiveWithDescriptionDisplayed(): void
    {
        $definition = $this->createSimpleDefinition(
            'test',
            true,
            [new ConfigParameter(
                'TEST_VAR',
                'Test Variable',
                ParameterType::String,
                defaultValue: 'default',
                description: 'A helpful description',
            )],
        );

        // Capture output to verify description is printed
        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $io = $this->createInteractiveIoWithOutput("\n", $output);
        $values = $this->collector->collect($definition, $io, true);

        $this->assertSame(['TEST_VAR' => 'default'], $values);
        $this->assertStringContainsString('A helpful description', $output->fetch());
    }

    public function testInteractiveMultipleParametersWithMixedEnvVars(): void
    {
        // Only HOST is set via env var, PORT is not
        $this->envVarReader->set('HOST', 'env-host.example.com');

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

        // First prompt: Enter (accepts env var 'env-host.example.com')
        // Second prompt: Enter (accepts default '5432')
        $io = $this->createInteractiveIo("\n\n");
        $values = $this->collector->collect($definition, $io, true);

        $this->assertSame('env-host.example.com', $values['HOST']);
        $this->assertSame('5432', $values['PORT']);
    }

    public function testInteractiveSecretPreConfiguredHintInOutput(): void
    {
        $this->envVarReader->set('MY_SECRET', 's3cret');

        $definition = $this->createSimpleDefinition(
            'test',
            true,
            [new ConfigParameter(
                'MY_SECRET',
                'Secret Key',
                ParameterType::Secret,
            )],
        );

        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $io = $this->createInteractiveIoWithOutput("\n", $output);
        $this->collector->collect($definition, $io, true);

        $rendered = $output->fetch();
        $this->assertStringContainsString('MY_SECRET is already configured', $rendered);
    }

    public function testInteractiveDisplaysParameterHintBeforePrompt(): void
    {
        $definition = $this->createHintProvidingDefinition(
            [
                new ConfigParameter('FIRST_VAR', 'First', ParameterType::String, defaultValue: 'aaa'),
                new ConfigParameter('SECOND_VAR', 'Second', ParameterType::String, defaultValue: 'bbb'),
            ],
            function (string $envVarName, array $collectedSoFar): ?string {
                if ($envVarName === 'SECOND_VAR') {
                    return 'Hint for second based on: ' . ($collectedSoFar['FIRST_VAR'] ?? 'unknown');
                }

                return null;
            },
        );

        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $io = $this->createInteractiveIoWithOutput("\n\n", $output);
        $values = $this->collector->collect($definition, $io, true);

        $this->assertSame(['FIRST_VAR' => 'aaa', 'SECOND_VAR' => 'bbb'], $values);
        $rendered = $output->fetch();
        $this->assertStringContainsString('Hint for second based on: aaa', $rendered);
    }

    public function testNonInteractiveDoesNotCallParameterHint(): void
    {
        $hintCalled = false;
        $definition = $this->createHintProvidingDefinition(
            [
                new ConfigParameter('TEST_VAR', 'Test', ParameterType::String, defaultValue: 'val'),
            ],
            function () use (&$hintCalled): ?string {
                $hintCalled = true;

                return 'Should not appear';
            },
        );

        $io = $this->createNonInteractiveIo();
        $this->collector->collect($definition, $io, false);

        $this->assertFalse($hintCalled);
    }

    public function testHintNotCalledForNonHintDefinition(): void
    {
        $definition = $this->createSimpleDefinition(
            'test',
            true,
            [new ConfigParameter('TEST_VAR', 'Test', ParameterType::String, defaultValue: 'val')],
        );

        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $io = $this->createInteractiveIoWithOutput("\n", $output);
        $values = $this->collector->collect($definition, $io, true);

        $this->assertSame(['TEST_VAR' => 'val'], $values);
        $rendered = $output->fetch();
        // note blocks contain "!" prefix — verify none is present
        $this->assertStringNotContainsString('! ', $rendered);
    }

    /**
     * Creates a simple definition with the given key, required flag, and parameters.
     *
     * @param list<ConfigParameter> $parameters
     */
    private function createSimpleDefinition(
        string $key,
        bool $required,
        array $parameters,
    ): EnvVarDefinitionInterface {
        return new class($key, $required, $parameters)
            implements EnvVarDefinitionInterface
        {
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
                    $name = $param->getEnvVarName();
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

    /**
     * Creates a definition that also implements ParameterHintProviderInterface.
     *
     * @param list<ConfigParameter> $parameters
     * @param \Closure(string, array<string, string>): ?string $hintCallback
     */
    private function createHintProvidingDefinition(
        array $parameters,
        \Closure $hintCallback,
    ): EnvVarDefinitionInterface {
        return new class($parameters, $hintCallback)
            implements EnvVarDefinitionInterface, ParameterHintProviderInterface
        {
            public function __construct(
                private readonly array $parameters,
                private readonly \Closure $hintCallback,
            ) {
            }

            public function getKey(): string
            {
                return 'hint-test';
            }

            public function getLabel(): string
            {
                return 'Hint Test';
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
                return $this->parameters;
            }

            public function resolveEnvVars(array $collectedValues): array
            {
                $result = [];
                foreach ($this->parameters as $param) {
                    $name = $param->getEnvVarName();
                    $result[$name] = $collectedValues[$name] ?? '';
                }

                return $result;
            }

            public function validate(array $collectedValues): array
            {
                return [];
            }

            public function getParameterHint(string $envVarName, array $collectedSoFar): ?string
            {
                return ($this->hintCallback)($envVarName, $collectedSoFar);
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

    /**
     * Creates an interactive SymfonyStyle with simulated user input.
     *
     * @param string $input The simulated keystrokes (e.g., "value\n" for typing "value" + Enter)
     */
    private function createInteractiveIo(string $input): SymfonyStyle
    {
        return $this->createInteractiveIoWithOutput($input, new NullOutput());
    }

    private function createInteractiveIoWithOutput(
        string $input,
        \Symfony\Component\Console\Output\OutputInterface $output,
    ): SymfonyStyle {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $input);
        rewind($stream);

        $arrayInput = new ArrayInput([]);
        $arrayInput->setInteractive(true);
        $arrayInput->setStream($stream);

        return new SymfonyStyle($arrayInput, $output);
    }
}
