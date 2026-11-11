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

use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Definitions\MercureEnvVarDefinition;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
final class MercureEnvVarDefinitionTest extends TestCase
{
    private MercureEnvVarDefinition $definition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->definition = new MercureEnvVarDefinition();
    }

    public function testMetadata(): void
    {
        $this->assertSame('mercure', $this->definition->getKey());
        $this->assertTrue($this->definition->isRequired());
        $this->assertSame('pimcore/studio-backend-bundle', $this->definition->getSectionName());
    }

    public function testResolveEnvVars(): void
    {
        $envVars = $this->definition->resolveEnvVars([
            'MERCURE_JWT_KEY' => 'my-secret-key-that-is-long-enough',
            'MERCURE_URL' => 'http://localhost/hub',
            'MERCURE_SERVER_URL' => 'http://mercure/.well-known/mercure',
        ]);

        $this->assertSame('my-secret-key-that-is-long-enough', $envVars['MERCURE_JWT_KEY']);
        $this->assertSame('http://localhost/hub', $envVars['MERCURE_URL']);
        $this->assertSame('http://mercure/.well-known/mercure', $envVars['MERCURE_SERVER_URL']);
    }

    public function testValidateRejectsEmptyJwtKey(): void
    {
        $errors = $this->definition->validate([
            'MERCURE_JWT_KEY' => '',
            'MERCURE_URL' => 'http://localhost/hub',
            'MERCURE_SERVER_URL' => 'http://mercure/.well-known/mercure',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('JWT', $errors[0]);
    }

    public function testValidateRejectsShortJwtKey(): void
    {
        $errors = $this->definition->validate([
            'MERCURE_JWT_KEY' => 'short',
            'MERCURE_URL' => 'http://localhost/hub',
            'MERCURE_SERVER_URL' => 'http://mercure/.well-known/mercure',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('32 characters', $errors[0]);
    }

    public function testValidateRejectsInvalidUrl(): void
    {
        $errors = $this->definition->validate([
            'MERCURE_JWT_KEY' => str_repeat('a', 32),
            'MERCURE_URL' => 'not-a-url',
            'MERCURE_SERVER_URL' => 'http://mercure/.well-known/mercure',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid', $errors[0]);
    }

    public function testValidateAcceptsValidConfig(): void
    {
        $errors = $this->definition->validate([
            'MERCURE_JWT_KEY' => str_repeat('a', 64),
            'MERCURE_URL' => 'http://localhost/hub',
            'MERCURE_SERVER_URL' => 'http://mercure/.well-known/mercure',
        ]);

        $this->assertSame([], $errors);
    }
}
