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

use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Definitions\OpenSearchEnvVarDefinition;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\SearchEngineDefinitionInterface;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
final class OpenSearchEnvVarDefinitionTest extends TestCase
{
    private OpenSearchEnvVarDefinition $definition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->definition = new OpenSearchEnvVarDefinition();
    }

    public function testImplementsSearchEngineDefinitionInterface(): void
    {
        $this->assertInstanceOf(SearchEngineDefinitionInterface::class, $this->definition);
    }

    public function testMetadata(): void
    {
        $this->assertSame('opensearch', $this->definition->getKey());
        $this->assertSame('OpenSearch', $this->definition->getLabel());
        $this->assertTrue($this->definition->isRequired());
        $this->assertSame('pimcore/opensearch-client', $this->definition->getSectionName());
    }

    public function testResolveEnvVarsBuildsDsn(): void
    {
        $envVars = $this->definition->resolveEnvVars([
            'PIMCORE_OPENSEARCH_HOST' => 'https://os:9200',
            'PIMCORE_OPENSEARCH_USERNAME' => 'admin',
            'PIMCORE_OPENSEARCH_PASSWORD' => 'secret',
            'PIMCORE_OPENSEARCH_SSL_VERIFY' => 'false',
        ]);

        $this->assertArrayHasKey('PIMCORE_OPENSEARCH_DSN', $envVars);
        $dsn = $envVars['PIMCORE_OPENSEARCH_DSN'];
        $this->assertStringStartsWith('opensearch://', $dsn);
        $this->assertStringContainsString('admin:secret@', $dsn);
        $this->assertStringContainsString('os:9200', $dsn);
        $this->assertStringContainsString('ssl_verify=false', $dsn);
    }

    public function testResolveEnvVarsBuildsDsnWithEmptyPassword(): void
    {
        $envVars = $this->definition->resolveEnvVars([
            'PIMCORE_OPENSEARCH_HOST' => 'https://os:9200',
            'PIMCORE_OPENSEARCH_USERNAME' => 'admin',
            'PIMCORE_OPENSEARCH_PASSWORD' => '',
            'PIMCORE_OPENSEARCH_SSL_VERIFY' => 'true',
        ]);

        $dsn = $envVars['PIMCORE_OPENSEARCH_DSN'];
        // With empty password, only username in userinfo
        $this->assertStringContainsString('admin@', $dsn);
        $this->assertStringNotContainsString('admin:@', $dsn);
    }

    public function testResolveEnvVarsBuildsDsnWithNoCredentials(): void
    {
        $envVars = $this->definition->resolveEnvVars([
            'PIMCORE_OPENSEARCH_HOST' => 'https://os:9200',
            'PIMCORE_OPENSEARCH_USERNAME' => '',
            'PIMCORE_OPENSEARCH_PASSWORD' => '',
            'PIMCORE_OPENSEARCH_SSL_VERIFY' => 'true',
        ]);

        $dsn = $envVars['PIMCORE_OPENSEARCH_DSN'];
        // No userinfo at all
        $this->assertStringStartsWith('opensearch://os:9200', $dsn);
    }

    public function testValidateRejectsEmptyHost(): void
    {
        $errors = $this->definition->validate([
            'PIMCORE_OPENSEARCH_HOST' => '',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('host', strtolower($errors[0]));
    }

    public function testValidateRejectsInvalidUrl(): void
    {
        $errors = $this->definition->validate([
            'PIMCORE_OPENSEARCH_HOST' => 'not-a-url',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid', $errors[0]);
    }

    public function testValidateAcceptsValidConfig(): void
    {
        $errors = $this->definition->validate([
            'PIMCORE_OPENSEARCH_HOST' => 'https://localhost:9200',
            'PIMCORE_OPENSEARCH_USERNAME' => 'admin',
            'PIMCORE_OPENSEARCH_PASSWORD' => 'admin',
        ]);

        // Only connection test errors expected (no server running)
        foreach ($errors as $error) {
            $this->assertStringNotContainsString('required', strtolower($error));
        }
    }

    public function testParametersContainExpectedFields(): void
    {
        $params = $this->definition->getParameters();
        $names = array_map(fn ($p) => $p->getEnvVarName(), $params);

        $this->assertContains('PIMCORE_OPENSEARCH_HOST', $names);
        $this->assertContains('PIMCORE_OPENSEARCH_USERNAME', $names);
        $this->assertContains('PIMCORE_OPENSEARCH_PASSWORD', $names);
        $this->assertContains('PIMCORE_OPENSEARCH_SSL_VERIFY', $names);
    }

    public function testPasswordIsOptional(): void
    {
        $params = $this->definition->getParameters();
        $passwordParam = null;
        foreach ($params as $p) {
            if ($p->getEnvVarName() === 'PIMCORE_OPENSEARCH_PASSWORD') {
                $passwordParam = $p;
                break;
            }
        }

        $this->assertNotNull($passwordParam);
        $this->assertFalse($passwordParam->isRequired());
    }
}
