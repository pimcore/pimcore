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

use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Definitions\GotenbergEnvVarDefinition;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
final class GotenbergEnvVarDefinitionTest extends TestCase
{
    private GotenbergEnvVarDefinition $definition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->definition = new GotenbergEnvVarDefinition();
    }

    public function testMetadata(): void
    {
        $this->assertSame('gotenberg', $this->definition->getKey());
        $this->assertFalse($this->definition->isRequired());
        $this->assertSame('pimcore/pimcore', $this->definition->getSectionName());
    }

    public function testGetParametersReturnsSingleUrlParameter(): void
    {
        $params = $this->definition->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('GOTENBERG_BASE_URL', $params[0]->getEnvVarName());
    }

    public function testResolveEnvVars(): void
    {
        $envVars = $this->definition->resolveEnvVars([
            'GOTENBERG_BASE_URL' => 'http://gotenberg:3000',
        ]);

        $this->assertSame('http://gotenberg:3000', $envVars['GOTENBERG_BASE_URL']);
    }

    public function testResolveEnvVarsUsesDefaultWhenMissing(): void
    {
        $envVars = $this->definition->resolveEnvVars([]);

        $this->assertSame('http://gotenberg:3000', $envVars['GOTENBERG_BASE_URL']);
    }

    public function testValidateAcceptsValidUrl(): void
    {
        $errors = $this->definition->validate([
            'GOTENBERG_BASE_URL' => 'http://gotenberg:3000',
        ]);

        $this->assertSame([], $errors);
    }

    public function testValidateAcceptsHttpsUrl(): void
    {
        $errors = $this->definition->validate([
            'GOTENBERG_BASE_URL' => 'https://gotenberg.example.com',
        ]);

        $this->assertSame([], $errors);
    }

    public function testValidateRejectsEmptyUrl(): void
    {
        $errors = $this->definition->validate([
            'GOTENBERG_BASE_URL' => '',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('required', strtolower($errors[0]));
    }

    public function testValidateRejectsInvalidUrl(): void
    {
        $errors = $this->definition->validate([
            'GOTENBERG_BASE_URL' => 'not-a-url',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid', $errors[0]);
    }
}
