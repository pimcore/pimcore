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

use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Definitions\AmqpMessengerEnvVarDefinition;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\MessengerTransportDefinitionInterface;
use Pimcore\Bundle\InstallBundle\EnvVarDefinition\ParameterHintProviderInterface;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
final class AmqpMessengerEnvVarDefinitionTest extends TestCase
{
    private AmqpMessengerEnvVarDefinition $definition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->definition = new AmqpMessengerEnvVarDefinition();
    }

    public function testImplementsMessengerTransportDefinitionInterface(): void
    {
        $this->assertInstanceOf(MessengerTransportDefinitionInterface::class, $this->definition);
    }

    public function testImplementsParameterHintProviderInterface(): void
    {
        $this->assertInstanceOf(ParameterHintProviderInterface::class, $this->definition);
    }

    public function testMetadata(): void
    {
        $this->assertSame('messenger-amqp', $this->definition->getKey());
        $this->assertSame('Messenger Transport (AMQP)', $this->definition->getLabel());
        $this->assertTrue($this->definition->isRequired());
        $this->assertSame('pimcore/pimcore', $this->definition->getSectionName());
    }

    public function testResolveEnvVars(): void
    {
        $envVars = $this->definition->resolveEnvVars([
            'PIMCORE_MESSENGER_TRANSPORT_DSN_PREFIX' => 'amqp://guest:guest@127.0.0.1:5672/%2f/',
        ]);

        $this->assertSame(
            'amqp://guest:guest@127.0.0.1:5672/%2f/',
            $envVars['PIMCORE_MESSENGER_TRANSPORT_DSN_PREFIX'],
        );
    }

    public function testValidateRejectsEmptyUrl(): void
    {
        $errors = $this->definition->validate(['PIMCORE_MESSENGER_TRANSPORT_DSN_PREFIX' => '']);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('required', strtolower($errors[0]));
    }

    public function testValidateRejectsInvalidScheme(): void
    {
        $errors = $this->definition->validate([
            'PIMCORE_MESSENGER_TRANSPORT_DSN_PREFIX' => 'http://guest:guest@localhost:5672/%2f/',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('amqp://', $errors[0]);
    }

    public function testValidateRejectsMalformedUrl(): void
    {
        $errors = $this->definition->validate([
            'PIMCORE_MESSENGER_TRANSPORT_DSN_PREFIX' => 'not-a-url',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid', $errors[0]);
    }

    public function testValidateRejectsDsnWithoutTrailingSlash(): void
    {
        $errors = $this->definition->validate([
            'PIMCORE_MESSENGER_TRANSPORT_DSN_PREFIX' => 'amqp://guest:guest@127.0.0.1:5672/%2f',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('must end with "/"', $errors[0]);
    }

    public function testValidateAcceptsDsnFormatWithTrailingSlash(): void
    {
        // Connection will fail in test env (no AMQP broker), but we verify
        // that no FORMAT errors are returned — only connection errors.
        $errors = $this->definition->validate([
            'PIMCORE_MESSENGER_TRANSPORT_DSN_PREFIX' => 'amqp://guest:guest@127.0.0.1:5672/%2f/',
        ]);

        // Either empty (broker reachable) or connection error (not a format error)
        foreach ($errors as $error) {
            $this->assertStringNotContainsString('must end with', $error);
            $this->assertStringNotContainsString('required', strtolower($error));
            $this->assertStringNotContainsString('Invalid', $error);
        }
    }

    public function testDefaultValueHasTrailingSlash(): void
    {
        $params = $this->definition->getParameters();
        $this->assertCount(1, $params);
        $this->assertTrue(
            str_ends_with($params[0]->getDefaultValue(), '/'),
            'Default AMQP DSN must end with trailing /',
        );
    }

    public function testParameterHintReturnsAmqpFormat(): void
    {
        $hint = $this->definition->getParameterHint('PIMCORE_MESSENGER_TRANSPORT_DSN_PREFIX', []);

        $this->assertNotNull($hint);
        $this->assertStringContainsString('amqp://', $hint);
        $this->assertStringContainsString('/%2f/', $hint);
    }

    public function testParametersContainDsn(): void
    {
        $params = $this->definition->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('PIMCORE_MESSENGER_TRANSPORT_DSN_PREFIX', $params[0]->getEnvVarName());
    }
}
