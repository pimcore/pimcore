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

use Pimcore\Bundle\InstallBundle\EnvVarDefinition\Definitions\MailerEnvVarDefinition;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
final class MailerEnvVarDefinitionTest extends TestCase
{
    private MailerEnvVarDefinition $definition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->definition = new MailerEnvVarDefinition();
    }

    public function testMetadata(): void
    {
        $this->assertSame('mailer', $this->definition->getKey());
        $this->assertFalse($this->definition->isRequired());
        $this->assertSame('symfony/mailer', $this->definition->getSectionName());
    }

    public function testGetParametersReturnsSingleParameter(): void
    {
        $params = $this->definition->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('MAILER_DSN', $params[0]->getEnvVarName());
    }

    public function testResolveEnvVars(): void
    {
        $envVars = $this->definition->resolveEnvVars([
            'MAILER_DSN' => 'smtp://user:pass@mailpit:1025',
        ]);

        $this->assertSame('smtp://user:pass@mailpit:1025', $envVars['MAILER_DSN']);
    }

    public function testResolveEnvVarsUsesDefaultWhenMissing(): void
    {
        $envVars = $this->definition->resolveEnvVars([]);

        $this->assertSame('null://null', $envVars['MAILER_DSN']);
    }

    public function testValidateAcceptsSmtpDsn(): void
    {
        $errors = $this->definition->validate([
            'MAILER_DSN' => 'smtp://user:pass@mailpit:1025',
        ]);

        $this->assertSame([], $errors);
    }

    public function testValidateAcceptsSmtpsDsn(): void
    {
        $errors = $this->definition->validate([
            'MAILER_DSN' => 'smtps://user:pass@smtp.example.com:465',
        ]);

        $this->assertSame([], $errors);
    }

    public function testValidateAcceptsNullDsn(): void
    {
        $errors = $this->definition->validate([
            'MAILER_DSN' => 'null://null',
        ]);

        $this->assertSame([], $errors);
    }

    public function testValidateAcceptsNativeDsn(): void
    {
        $errors = $this->definition->validate([
            'MAILER_DSN' => 'native://default',
        ]);

        $this->assertSame([], $errors);
    }

    public function testValidateAcceptsSendmailDsn(): void
    {
        $errors = $this->definition->validate([
            'MAILER_DSN' => 'sendmail://default',
        ]);

        $this->assertSame([], $errors);
    }

    public function testValidateRejectsEmptyDsn(): void
    {
        $errors = $this->definition->validate([
            'MAILER_DSN' => '',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('required', strtolower($errors[0]));
    }

    public function testValidateRejectsInvalidScheme(): void
    {
        $errors = $this->definition->validate([
            'MAILER_DSN' => 'ftp://user:pass@host:21',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('scheme', strtolower($errors[0]));
    }

    public function testValidateRejectsInvalidUrl(): void
    {
        $errors = $this->definition->validate([
            'MAILER_DSN' => 'not-a-valid-dsn',
        ]);

        $this->assertNotEmpty($errors);
    }
}
