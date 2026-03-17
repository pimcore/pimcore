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

use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Definitions\DatabaseEnvVarDefinition;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
final class DatabaseEnvVarDefinitionTest extends TestCase
{
    private DatabaseEnvVarDefinition $definition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->definition = new DatabaseEnvVarDefinition();
    }

    public function testMetadata(): void
    {
        $this->assertSame('database', $this->definition->getKey());
        $this->assertSame('Database (MySQL/MariaDB)', $this->definition->getLabel());
        $this->assertTrue($this->definition->isRequired());
        $this->assertSame('pimcore/pimcore', $this->definition->getSectionName());
    }

    public function testParametersStructure(): void
    {
        $params = $this->definition->getParameters();
        $this->assertCount(5, $params);

        $names = array_map(fn ($p) => $p->getEnvVarName(), $params);
        $this->assertSame(
            ['DATABASE_HOST', 'DATABASE_PORT', 'DATABASE_NAME', 'DATABASE_USER', 'DATABASE_PASSWORD'],
            $names,
        );

        // All DB params are transient (assembled into DATABASE_URL)
        foreach ($params as $param) {
            $this->assertTrue($param->isTransient(), $param->getEnvVarName() . ' should be transient');
        }
    }

    public function testResolveEnvVarsAssemblesDsn(): void
    {
        $envVars = $this->definition->resolveEnvVars([
            'DATABASE_HOST' => 'db.example.com',
            'DATABASE_PORT' => '3307',
            'DATABASE_NAME' => 'mydb',
            'DATABASE_USER' => 'myuser',
            'DATABASE_PASSWORD' => 's3cr3t',
        ]);

        $this->assertArrayHasKey('DATABASE_URL', $envVars);
        $this->assertSame(
            'mysql://myuser:s3cr3t@db.example.com:3307/mydb',
            $envVars['DATABASE_URL'],
        );
    }

    public function testResolveEnvVarsEncodesSpecialChars(): void
    {
        $envVars = $this->definition->resolveEnvVars([
            'DATABASE_HOST' => '127.0.0.1',
            'DATABASE_PORT' => '3306',
            'DATABASE_NAME' => 'pimcore',
            'DATABASE_USER' => 'user@host',
            'DATABASE_PASSWORD' => 'p@ss:w/rd',
        ]);

        $this->assertStringContainsString('user%40host', $envVars['DATABASE_URL']);
        $this->assertStringContainsString('p%40ss%3Aw%2Frd', $envVars['DATABASE_URL']);
    }

    public function testResolveEnvVarsWithEmptyPassword(): void
    {
        $envVars = $this->definition->resolveEnvVars([
            'DATABASE_HOST' => '127.0.0.1',
            'DATABASE_PORT' => '3306',
            'DATABASE_NAME' => 'pimcore',
            'DATABASE_USER' => 'root',
            'DATABASE_PASSWORD' => '',
        ]);

        // Should not have the :password@ part
        $this->assertSame(
            'mysql://root@127.0.0.1:3306/pimcore',
            $envVars['DATABASE_URL'],
        );
    }

    public function testValidateReturnsErrorsForEmptyHost(): void
    {
        $errors = $this->definition->validate([
            'DATABASE_HOST' => '',
            'DATABASE_PORT' => '3306',
            'DATABASE_NAME' => 'pimcore',
            'DATABASE_USER' => 'pimcore',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('host', $errors[0]);
    }

    public function testValidateReturnsErrorsForInvalidPort(): void
    {
        $errors = $this->definition->validate([
            'DATABASE_HOST' => '127.0.0.1',
            'DATABASE_PORT' => '99999',
            'DATABASE_NAME' => 'pimcore',
            'DATABASE_USER' => 'pimcore',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('port', $errors[0]);
    }

    public function testValidateReturnsConnectionErrorForUnreachableHost(): void
    {
        $errors = $this->definition->validate([
            'DATABASE_HOST' => '192.0.2.1',    // RFC 5737 TEST-NET — always unreachable
            'DATABASE_PORT' => '3306',
            'DATABASE_NAME' => 'pimcore',
            'DATABASE_USER' => 'pimcore',
            'DATABASE_PASSWORD' => 'secret',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('connection failed', strtolower($errors[0]));
    }
}
