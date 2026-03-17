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

    public function testSingleDsnParameter(): void
    {
        $params = $this->definition->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('PIMCORE_ELASTICSEARCH_DSN', $params[0]->getEnvVarName());
        $this->assertNotNull($params[0]->getDefaultValue());
        $this->assertStringStartsWith('elasticsearch://', $params[0]->getDefaultValue());
    }

    public function testResolveEnvVarsPassesThrough(): void
    {
        $dsn = 'elasticsearch://elastic:secret@es:9200?ssl_verify=true';
        $envVars = $this->definition->resolveEnvVars(['PIMCORE_ELASTICSEARCH_DSN' => $dsn]);
        $this->assertSame(['PIMCORE_ELASTICSEARCH_DSN' => $dsn], $envVars);
    }

    public function testValidateRejectsEmptyDsn(): void
    {
        $errors = $this->definition->validate(['PIMCORE_ELASTICSEARCH_DSN' => '']);
        $this->assertNotEmpty($errors);
    }

    public function testValidateRejectsInvalidScheme(): void
    {
        $errors = $this->definition->validate([
            'PIMCORE_ELASTICSEARCH_DSN' => 'http://localhost:9200',
        ]);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('scheme', strtolower($errors[0]));
    }

    public function testValidateRejectsMissingHost(): void
    {
        $errors = $this->definition->validate([
            'PIMCORE_ELASTICSEARCH_DSN' => 'elasticsearch://',
        ]);
        $this->assertNotEmpty($errors);
        // PHP 8.4 parse_url() returns false for scheme-only URLs, so the error may be
        // "not a valid URL" instead of the specific "host" message.
        $lower = strtolower($errors[0]);
        $this->assertTrue(
            str_contains($lower, 'host') || str_contains($lower, 'not a valid url'),
            sprintf('Expected error about host or invalid URL, got: %s', $errors[0]),
        );
    }

    public function testValidateAcceptsValidDsn(): void
    {
        $errors = $this->definition->validate([
            'PIMCORE_ELASTICSEARCH_DSN' => 'elasticsearch://elastic:secret@localhost:9200?ssl_verify=true',
        ]);

        // Only connection test errors expected (no server running)
        foreach ($errors as $error) {
            $this->assertStringNotContainsString('scheme', strtolower($error));
            $this->assertStringNotContainsString('required', strtolower($error));
        }
    }
}
