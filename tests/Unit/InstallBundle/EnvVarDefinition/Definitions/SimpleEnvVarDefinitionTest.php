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

namespace Pimcore\Tests\Unit\InstallBundle\EnvVarDefinition\Definitions;

use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ConfigParameter;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Definitions\SimpleEnvVarDefinition;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterType;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
final class SimpleEnvVarDefinitionTest extends TestCase
{
    public function testMetadataReturnsConstructorValues(): void
    {
        $definition = new SimpleEnvVarDefinition(
            'my-key',
            'My Label',
            'my/section',
            [
                new ConfigParameter('MY_VAR', 'My Var', ParameterType::String),
            ],
        );

        $this->assertSame('my-key', $definition->getKey());
        $this->assertSame('My Label', $definition->getLabel());
        $this->assertSame('my/section', $definition->getSectionName());
        $this->assertTrue($definition->isRequired());
    }

    public function testIsRequiredReturnsFalseWhenConfigured(): void
    {
        $definition = new SimpleEnvVarDefinition(
            'optional-key',
            'Optional',
            'my/section',
            [
                new ConfigParameter('MY_VAR', 'My Var', ParameterType::String),
            ],
            required: false,
        );

        $this->assertFalse($definition->isRequired());
    }

    public function testGetParametersReturnsConstructorParameters(): void
    {
        $params = [
            new ConfigParameter('VAR_A', 'Var A', ParameterType::String),
            new ConfigParameter('VAR_B', 'Var B', ParameterType::Secret),
        ];

        $definition = new SimpleEnvVarDefinition(
            'test',
            'Test',
            'test/section',
            $params,
        );

        $this->assertSame($params, $definition->getParameters());
    }

    public function testResolveEnvVarsPassesThrough(): void
    {
        $definition = new SimpleEnvVarDefinition(
            'test',
            'Test',
            'test/section',
            [
                new ConfigParameter('VAR_A', 'Var A', ParameterType::String),
                new ConfigParameter('VAR_B', 'Var B', ParameterType::Secret),
            ],
        );

        $collected = ['VAR_A' => 'value-a', 'VAR_B' => 'secret-b'];
        $envVars = $definition->resolveEnvVars($collected);

        $this->assertSame('value-a', $envVars['VAR_A']);
        $this->assertSame('secret-b', $envVars['VAR_B']);
    }

    public function testResolveEnvVarsOnlyIncludesDefinedParameters(): void
    {
        $definition = new SimpleEnvVarDefinition(
            'test',
            'Test',
            'test/section',
            [
                new ConfigParameter('VAR_A', 'Var A', ParameterType::String),
            ],
        );

        $collected = ['VAR_A' => 'value-a', 'EXTRA' => 'ignored'];
        $envVars = $definition->resolveEnvVars($collected);

        $this->assertArrayHasKey('VAR_A', $envVars);
        $this->assertArrayNotHasKey('EXTRA', $envVars);
    }

    public function testValidateReturnsNoErrorsForValidValues(): void
    {
        $definition = new SimpleEnvVarDefinition(
            'test',
            'Test',
            'test/section',
            [
                new ConfigParameter('MY_VAR', 'My Var', ParameterType::String),
            ],
        );

        $errors = $definition->validate(['MY_VAR' => 'some-value']);
        $this->assertSame([], $errors);
    }

    public function testValidateRejectsEmptyRequiredParameter(): void
    {
        $definition = new SimpleEnvVarDefinition(
            'test',
            'Test',
            'test/section',
            [
                new ConfigParameter('MY_VAR', 'My Var', ParameterType::String),
            ],
        );

        $errors = $definition->validate(['MY_VAR' => '']);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('required', strtolower($errors[0]));
    }

    public function testValidateAllowsEmptyOptionalParameter(): void
    {
        $definition = new SimpleEnvVarDefinition(
            'test',
            'Test',
            'test/section',
            [
                new ConfigParameter(
                    'MY_VAR',
                    'My Var',
                    ParameterType::String,
                    required: false,
                ),
            ],
        );

        $errors = $definition->validate(['MY_VAR' => '']);
        $this->assertSame([], $errors);
    }

    public function testValidateRejectsInvalidUrl(): void
    {
        $definition = new SimpleEnvVarDefinition(
            'test',
            'Test',
            'test/section',
            [
                new ConfigParameter('MY_URL', 'My URL', ParameterType::Url),
            ],
        );

        $errors = $definition->validate(['MY_URL' => 'not-a-url']);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid', $errors[0]);
    }

    public function testValidateAcceptsValidUrl(): void
    {
        $definition = new SimpleEnvVarDefinition(
            'test',
            'Test',
            'test/section',
            [
                new ConfigParameter('MY_URL', 'My URL', ParameterType::Url),
            ],
        );

        $errors = $definition->validate(['MY_URL' => 'https://example.com:9200']);
        $this->assertSame([], $errors);
    }

    public function testValidateCollectsMultipleErrors(): void
    {
        $definition = new SimpleEnvVarDefinition(
            'test',
            'Test',
            'test/section',
            [
                new ConfigParameter('VAR_A', 'Var A', ParameterType::String),
                new ConfigParameter('VAR_B', 'Var B', ParameterType::String),
            ],
        );

        $errors = $definition->validate(['VAR_A' => '', 'VAR_B' => '']);
        $this->assertCount(2, $errors);
    }

    public function testValidateHandlesMissingCollectedValues(): void
    {
        $definition = new SimpleEnvVarDefinition(
            'test',
            'Test',
            'test/section',
            [
                new ConfigParameter('MY_VAR', 'My Var', ParameterType::String),
            ],
        );

        $errors = $definition->validate([]);
        $this->assertNotEmpty($errors);
    }
}
