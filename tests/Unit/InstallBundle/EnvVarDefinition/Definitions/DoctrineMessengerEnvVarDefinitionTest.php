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

use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Definitions\DoctrineMessengerEnvVarDefinition;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\MessengerTransportDefinitionInterface;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
final class DoctrineMessengerEnvVarDefinitionTest extends TestCase
{
    private DoctrineMessengerEnvVarDefinition $definition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->definition = new DoctrineMessengerEnvVarDefinition();
    }

    public function testImplementsMessengerTransportDefinitionInterface(): void
    {
        $this->assertInstanceOf(MessengerTransportDefinitionInterface::class, $this->definition);
    }

    public function testMetadata(): void
    {
        $this->assertSame('messenger-doctrine', $this->definition->getKey());
        $this->assertSame('Messenger Transport (Doctrine)', $this->definition->getLabel());
        $this->assertTrue($this->definition->isRequired());
        $this->assertSame('pimcore/pimcore', $this->definition->getSectionName());
    }

    public function testResolveEnvVars(): void
    {
        $envVars = $this->definition->resolveEnvVars([]);

        $this->assertSame(
            'doctrine://default',
            $envVars['PIMCORE_MESSENGER_TRANSPORT_DSN'],
        );
    }

    public function testHasNoUserParameters(): void
    {
        $this->assertSame([], $this->definition->getParameters());
    }

    public function testValidateAlwaysPasses(): void
    {
        $errors = $this->definition->validate([]);

        $this->assertSame([], $errors);
    }
}
