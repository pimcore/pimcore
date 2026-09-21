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

    public function testSingleDatabaseUrlParameter(): void
    {
        $params = $this->definition->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('DATABASE_URL', $params[0]->getEnvVarName());
        $this->assertNotNull($params[0]->getDefaultValue());
        $this->assertStringStartsWith('mysql://', $params[0]->getDefaultValue());
    }

    public function testResolveEnvVarsPassesThrough(): void
    {
        $dsn = 'mysql://myuser:s3cr3t@db.example.com:3307/mydb';
        $envVars = $this->definition->resolveEnvVars(['DATABASE_URL' => $dsn]);

        $this->assertSame(['DATABASE_URL' => $dsn], $envVars);
    }

    public function testValidateRejectsEmptyUrl(): void
    {
        $errors = $this->definition->validate(['DATABASE_URL' => '']);
        $this->assertNotEmpty($errors);
    }

    public function testValidateRejectsInvalidScheme(): void
    {
        $errors = $this->definition->validate(['DATABASE_URL' => 'http://localhost/pimcore']);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('scheme', strtolower($errors[0]));
    }

    public function testValidateRejectsMissingHost(): void
    {
        $errors = $this->definition->validate(['DATABASE_URL' => 'mysql:///pimcore']);
        $this->assertNotEmpty($errors);
        // PHP 8.4 parse_url() returns false for this input, so the error may be
        // "not a valid URL" instead of the specific "host" message.
        $lower = strtolower($errors[0]);
        $this->assertTrue(
            str_contains($lower, 'host') || str_contains($lower, 'not a valid url'),
            sprintf('Expected error about host or invalid URL, got: %s', $errors[0]),
        );
    }

    public function testValidateRejectsMissingDbName(): void
    {
        $errors = $this->definition->validate(['DATABASE_URL' => 'mysql://user@localhost:3306']);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('database name', strtolower($errors[0]));
    }

    public function testValidateRejectsInvalidPort(): void
    {
        $errors = $this->definition->validate(['DATABASE_URL' => 'mysql://user@localhost:99999/pimcore']);
        $this->assertNotEmpty($errors);
        // PHP 8.4 parse_url() returns false for ports > 65535, so the error may be
        // "not a valid URL" instead of the specific "port" message.
        $lower = strtolower($errors[0]);
        $this->assertTrue(
            str_contains($lower, 'port') || str_contains($lower, 'not a valid url'),
            sprintf('Expected error about port or invalid URL, got: %s', $errors[0]),
        );
    }

    public function testValidateReturnsConnectionErrorForUnreachableHost(): void
    {
        $errors = $this->definition->validate([
            'DATABASE_URL' => 'mysql://pimcore:secret@192.0.2.1:3306/pimcore',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('connection failed', strtolower($errors[0]));
    }
}
