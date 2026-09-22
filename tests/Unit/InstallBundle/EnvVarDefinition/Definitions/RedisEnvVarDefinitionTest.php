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

use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Definitions\RedisEnvVarDefinition;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
final class RedisEnvVarDefinitionTest extends TestCase
{
    private RedisEnvVarDefinition $definition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->definition = new RedisEnvVarDefinition();
    }

    public function testMetadata(): void
    {
        $this->assertSame('redis', $this->definition->getKey());
        $this->assertFalse($this->definition->isRequired());
        $this->assertSame('pimcore/pimcore', $this->definition->getSectionName());
    }

    public function testResolveEnvVars(): void
    {
        $envVars = $this->definition->resolveEnvVars([
            'REDIS_URL' => 'redis://myredis:6380/1',
        ]);

        $this->assertSame('redis://myredis:6380/1', $envVars['REDIS_URL']);
    }

    public function testValidateRejectsEmptyUrl(): void
    {
        $errors = $this->definition->validate(['REDIS_URL' => '']);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('required', strtolower($errors[0]));
    }

    public function testValidateRejectsMalformedUrl(): void
    {
        $errors = $this->definition->validate(['REDIS_URL' => 'not-a-url']);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid', $errors[0]);
    }

    public function testValidatePassesFormatCheckWithValidUrl(): void
    {
        // Valid format + ext-redis not loaded = no errors (I20 soft skip)
        // Valid format + ext-redis loaded = connection test may fail (that's OK)
        $errors = $this->definition->validate([
            'REDIS_URL' => 'redis://localhost:6379/0',
        ]);

        // If errors exist, they should be connection errors, not format errors
        foreach ($errors as $error) {
            $this->assertStringNotContainsString('required', strtolower($error));
            $this->assertStringNotContainsString('Invalid', $error);
        }
    }
}
