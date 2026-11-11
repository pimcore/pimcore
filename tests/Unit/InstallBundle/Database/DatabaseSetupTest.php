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

namespace Pimcore\Tests\Unit\InstallBundle\Database;

use Pimcore\Bundle\InstallBundle\Database\DatabaseSetup;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
final class DatabaseSetupTest extends TestCase
{
    private DatabaseSetup $setup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setup = new DatabaseSetup();
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(DatabaseSetup::class, $this->setup);
    }

    public function testInstallSqlFileExists(): void
    {
        $installSqlPath = __DIR__ . '/../../../../bundles/InstallBundle/dump/install.sql';
        $this->assertFileExists($installSqlPath, 'install.sql must exist for DatabaseSetup to work');
    }

    public function testValidateAdminCredentialsPassesForValidInput(): void
    {
        $errors = $this->setup->validateAdminCredentials([
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $this->assertSame([], $errors);
    }

    public function testValidateAdminCredentialsFailsForShortUsername(): void
    {
        $errors = $this->setup->validateAdminCredentials([
            'username' => 'ab',
            'password' => 'admin123',
        ]);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('username must be at least 4', $errors[0]);
    }

    public function testValidateAdminCredentialsFailsForShortPassword(): void
    {
        $errors = $this->setup->validateAdminCredentials([
            'username' => 'admin',
            'password' => 'ab',
        ]);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('password must be at least 4', $errors[0]);
    }

    public function testValidateAdminCredentialsFailsForBothTooShort(): void
    {
        $errors = $this->setup->validateAdminCredentials([
            'username' => 'ab',
            'password' => 'cd',
        ]);

        $this->assertCount(2, $errors);
        $this->assertStringContainsString('username', $errors[0]);
        $this->assertStringContainsString('password', $errors[1]);
    }

    public function testValidateAdminCredentialsPassesForExactlyFourCharacters(): void
    {
        $errors = $this->setup->validateAdminCredentials([
            'username' => 'abcd',
            'password' => 'efgh',
        ]);

        $this->assertSame([], $errors);
    }

    public function testValidateAdminCredentialsFailsForEmptyUsername(): void
    {
        $errors = $this->setup->validateAdminCredentials([
            'username' => '',
            'password' => 'admin123',
        ]);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('username must be at least 4', $errors[0]);
    }

    public function testValidateAdminCredentialsFailsForEmptyPassword(): void
    {
        $errors = $this->setup->validateAdminCredentials([
            'username' => 'admin',
            'password' => '',
        ]);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('password must be at least 4', $errors[0]);
    }
}
