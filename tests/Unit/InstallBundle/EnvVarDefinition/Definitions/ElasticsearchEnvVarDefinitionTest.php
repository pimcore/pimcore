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

use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Definitions\ElasticsearchEnvVarDefinition;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\SearchEngineDefinitionInterface;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
final class ElasticsearchEnvVarDefinitionTest extends TestCase
{
    private ElasticsearchEnvVarDefinition $definition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->definition = new ElasticsearchEnvVarDefinition();
    }

    public function testImplementsSearchEngineDefinitionInterface(): void
    {
        $this->assertInstanceOf(SearchEngineDefinitionInterface::class, $this->definition);
    }

    public function testMetadata(): void
    {
        $this->assertSame('elasticsearch', $this->definition->getKey());
        $this->assertSame('Elasticsearch', $this->definition->getLabel());
        $this->assertTrue($this->definition->isRequired());
        $this->assertSame('pimcore/elasticsearch-client', $this->definition->getSectionName());
    }

    public function testResolveEnvVarsBuildsDsn(): void
    {
        $envVars = $this->definition->resolveEnvVars([
            'PIMCORE_ELASTICSEARCH_HOST' => 'https://es:9200',
            'PIMCORE_ELASTICSEARCH_USERNAME' => 'elastic',
            'PIMCORE_ELASTICSEARCH_PASSWORD' => 'changeme',
            'PIMCORE_ELASTICSEARCH_SSL_VERIFY' => 'true',
        ]);

        $this->assertArrayHasKey('PIMCORE_ELASTICSEARCH_DSN', $envVars);
        $dsn = $envVars['PIMCORE_ELASTICSEARCH_DSN'];
        $this->assertStringStartsWith('elasticsearch://', $dsn);
        $this->assertStringContainsString('elastic:changeme@', $dsn);
        $this->assertStringContainsString('es:9200', $dsn);
        $this->assertStringContainsString('ssl_verify=true', $dsn);
    }

    public function testResolveEnvVarsBuildsDsnWithEmptyPassword(): void
    {
        $envVars = $this->definition->resolveEnvVars([
            'PIMCORE_ELASTICSEARCH_HOST' => 'https://es:9200',
            'PIMCORE_ELASTICSEARCH_USERNAME' => 'elastic',
            'PIMCORE_ELASTICSEARCH_PASSWORD' => '',
            'PIMCORE_ELASTICSEARCH_SSL_VERIFY' => 'true',
        ]);

        $dsn = $envVars['PIMCORE_ELASTICSEARCH_DSN'];
        $this->assertStringContainsString('elastic@', $dsn);
        $this->assertStringNotContainsString('elastic:@', $dsn);
    }

    public function testValidateRejectsEmptyHost(): void
    {
        $errors = $this->definition->validate([
            'PIMCORE_ELASTICSEARCH_HOST' => '',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('host', strtolower($errors[0]));
    }

    public function testValidateRejectsInvalidUrl(): void
    {
        $errors = $this->definition->validate([
            'PIMCORE_ELASTICSEARCH_HOST' => 'not-a-url',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid', $errors[0]);
    }

    public function testParametersContainExpectedFields(): void
    {
        $params = $this->definition->getParameters();
        $names = array_map(fn ($p) => $p->getEnvVarName(), $params);

        $this->assertContains('PIMCORE_ELASTICSEARCH_HOST', $names);
        $this->assertContains('PIMCORE_ELASTICSEARCH_USERNAME', $names);
        $this->assertContains('PIMCORE_ELASTICSEARCH_PASSWORD', $names);
        $this->assertContains('PIMCORE_ELASTICSEARCH_SSL_VERIFY', $names);
    }

    public function testPasswordIsOptional(): void
    {
        $params = $this->definition->getParameters();
        $passwordParam = null;
        foreach ($params as $p) {
            if ($p->getEnvVarName() === 'PIMCORE_ELASTICSEARCH_PASSWORD') {
                $passwordParam = $p;
                break;
            }
        }

        $this->assertNotNull($passwordParam);
        $this->assertFalse($passwordParam->isRequired());
    }
}
