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

namespace Pimcore\Tests\Unit\InstallBundle;

use Pimcore\Bundle\InstallBundle\DefinitionResolver;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\EnvVarDefinitionInterface;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Unit\InstallBundle\Support\NoopSearchEngineDefinition;

/**
 * Unit tests for DefinitionResolver::shouldSkipValidation() matching logic.
 *
 * @internal
 */
final class DefinitionResolverTest extends TestCase
{
    private DefinitionResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new DefinitionResolver();
    }

    public function testEmptySkipValidationReturnsFalse(): void
    {
        $definition = $this->createSimpleDefinition('database');

        $this->assertFalse(
            $this->resolver->shouldSkipValidation($definition, []),
        );
    }

    public function testNullEntrySkipsAll(): void
    {
        $definition = $this->createSimpleDefinition('database');

        $this->assertTrue(
            $this->resolver->shouldSkipValidation($definition, [null]),
        );
    }

    public function testMatchByKeyCaseInsensitive(): void
    {
        $definition = $this->createSimpleDefinition('opensearch');

        $this->assertTrue(
            $this->resolver->shouldSkipValidation($definition, ['OpenSearch']),
        );
    }

    public function testMatchByFqcn(): void
    {
        $definition = new NoopSearchEngineDefinition();
        $fqcn = NoopSearchEngineDefinition::class;

        $this->assertTrue(
            $this->resolver->shouldSkipValidation($definition, [$fqcn]),
        );
    }

    public function testMatchByShortClassNameCaseInsensitive(): void
    {
        $definition = new NoopSearchEngineDefinition();

        $this->assertTrue(
            $this->resolver->shouldSkipValidation(
                $definition,
                ['noopsearchenginedefinition'],
            ),
        );
    }

    public function testNoMatchReturnsFalse(): void
    {
        $definition = $this->createSimpleDefinition('database');

        $this->assertFalse(
            $this->resolver->shouldSkipValidation($definition, ['redis']),
        );
    }

    public function testMultipleValuesMatchesAny(): void
    {
        $definition = $this->createSimpleDefinition('redis');

        $this->assertTrue(
            $this->resolver->shouldSkipValidation(
                $definition,
                ['opensearch', 'redis'],
            ),
        );
    }

    private function createSimpleDefinition(string $key): EnvVarDefinitionInterface
    {
        return new class($key) implements EnvVarDefinitionInterface {
            public function __construct(private readonly string $key)
            {
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
                return true;
            }

            public function getSectionName(): string
            {
                return 'test';
            }

            public function getParameters(): array
            {
                return [];
            }

            public function resolveEnvVars(array $collectedValues): array
            {
                return [];
            }

            public function validate(array $collectedValues): array
            {
                return [];
            }
        };
    }
}
