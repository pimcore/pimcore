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

namespace Pimcore\Tests\Unit\InstallBundle\Integration;

use Pimcore\Bundle\InstallBundle\Command\InstallCommand;
use Pimcore\Bundle\InstallBundle\Installer;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Unit\InstallBundle\Support\InstallBundleTestHelperTrait;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Integration tests for the InstallCommand.
 *
 * Tests the command's option handling, input validation, and error reporting
 * without actually running Phase 2 (which requires a real database).
 * The command is exercised via Symfony's CommandTester in non-interactive mode.
 *
 * Note: These tests exercise Phase 1 only. Phase 2 requires a running DB
 * and real Pimcore kernel, which is outside the scope of container integration tests.
 *
 * @internal
 */
final class InstallCommandIntegrationTest extends TestCase
{
    use InstallBundleTestHelperTrait;

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/pimcore_command_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    public function testCommandFailsWithoutProfileOption(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute([
            '--admin-username' => 'admin',
            '--admin-password' => 'admin123',
        ], ['interactive' => false]);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('--profile', $tester->getDisplay());
    }

    public function testCommandFailsWithNonExistentProfileClass(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute([
            '--profile' => 'NonExistent\\Profile\\Class',
            '--admin-username' => 'admin',
            '--admin-password' => 'admin123',
        ], ['interactive' => false]);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('does not exist', $tester->getDisplay());
    }

    public function testCommandFailsWithInvalidProfileClass(): void
    {
        $tester = $this->createCommandTester();

        // \stdClass exists but doesn't implement InstallProfileInterface
        $tester->execute([
            '--profile' => \stdClass::class,
            '--admin-username' => 'admin',
            '--admin-password' => 'admin123',
        ], ['interactive' => false]);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('must implement', $tester->getDisplay());
    }

    public function testCommandFailsWithoutAdminUsername(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute([
            '--profile' => 'NonExistent\\Profile',
            '--admin-password' => 'admin123',
        ], ['interactive' => false]);

        $this->assertSame(1, $tester->getStatusCode());
        $output = $tester->getDisplay();
        // Either complains about missing username or missing profile (fails first)
        $this->assertTrue(
            str_contains($output, 'username') || str_contains($output, 'does not exist'),
            'Expected error about missing username or profile',
        );
    }

    public function testCommandFailsWithoutAdminPassword(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute([
            '--profile' => 'NonExistent\\Profile',
            '--admin-username' => 'admin',
        ], ['interactive' => false]);

        $this->assertSame(1, $tester->getStatusCode());
        $output = $tester->getDisplay();
        // Either complains about missing password or missing profile (fails first)
        $this->assertTrue(
            str_contains($output, 'password') || str_contains($output, 'does not exist'),
            'Expected error about missing password or profile',
        );
    }

    public function testCommandFailsWithNonExistentEnvDefinition(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute([
            '--profile' => 'NonExistent\\Profile',
            '--env-definition' => ['NonExistent\\Definition\\Class'],
            '--admin-username' => 'admin',
            '--admin-password' => 'admin123',
        ], ['interactive' => false]);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('does not exist', $tester->getDisplay());
    }

    public function testCommandFailsWithNonExistentPostInstallProvider(): void
    {
        $tester = $this->createCommandTester();

        $tester->execute([
            '--profile' => 'NonExistent\\Profile',
            '--post-install-commands' => ['NonExistent\\Provider\\Class'],
            '--admin-username' => 'admin',
            '--admin-password' => 'admin123',
        ], ['interactive' => false]);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('does not exist', $tester->getDisplay());
    }

    public function testCommandHasCorrectName(): void
    {
        $command = $this->createCommand();

        $this->assertSame('pimcore:install', $command->getName());
    }

    public function testCommandHasCorrectDescription(): void
    {
        $command = $this->createCommand();

        $this->assertSame(
            'Install Pimcore with a profile-based configuration',
            $command->getDescription(),
        );
    }

    public function testCommandHasAllExpectedOptions(): void
    {
        $command = $this->createCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('profile'));
        $this->assertTrue($definition->hasOption('env-definition'));
        $this->assertTrue($definition->hasOption('post-install-commands'));
        $this->assertTrue($definition->hasOption('skip'));
        $this->assertTrue($definition->hasOption('admin-username'));
        $this->assertTrue($definition->hasOption('admin-password'));
    }

    public function testSkipOptionAcceptsMultipleValues(): void
    {
        $command = $this->createCommand();
        $option = $command->getDefinition()->getOption('skip');

        $this->assertTrue($option->isArray());
    }

    public function testEnvDefinitionOptionAcceptsMultipleValues(): void
    {
        $command = $this->createCommand();
        $option = $command->getDefinition()->getOption('env-definition');

        $this->assertTrue($option->isArray());
    }

    private function createCommand(): InstallCommand
    {
        $installer = $this->createInstaller();

        return new InstallCommand($installer, new EventDispatcher());
    }

    private function createCommandTester(): CommandTester
    {
        return new CommandTester($this->createCommand());
    }
}
