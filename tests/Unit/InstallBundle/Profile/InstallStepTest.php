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

namespace Pimcore\Tests\Unit\InstallBundle\Profile;

use Pimcore\Bundle\InstallBundle\Profile\InstallStep;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
final class InstallStepTest extends TestCase
{
    /**
     * @return list<array{0: InstallStep, 1: string}>
     */
    public static function allCasesProvider(): array
    {
        return [
            [InstallStep::CollectAndValidate, 'collect_validate'],
            [InstallStep::WriteEnv, 'write_env'],
            [InstallStep::WriteDoctrineConfig, 'write_doctrine_config'],
            [InstallStep::BootKernel, 'boot_kernel'],
            [InstallStep::SetupDatabase, 'setup_database'],
            [InstallStep::ImportData, 'import_data'],
            [InstallStep::CreateAdmin, 'create_admin'],
            [InstallStep::RegisterBundles, 'register_bundles'],
            [InstallStep::RebootKernel, 'reboot_kernel'],
            [InstallStep::InstallBundles, 'install_bundles'],
            [InstallStep::InstallAssets, 'install_assets'],
            [InstallStep::RebuildClasses, 'rebuild_classes'],
            [InstallStep::MarkMigrations, 'mark_migrations'],
            [InstallStep::PostInstallCommands, 'post_install_commands'],
            [InstallStep::RunMaintenance, 'run_maintenance'],
            [InstallStep::ProfilePostInstall, 'profile_post_install'],
            [InstallStep::Finalize, 'finalize'],
        ];
    }

    public function testAllSeventeenCasesExist(): void
    {
        $cases = InstallStep::cases();

        $this->assertCount(17, $cases);
    }

    /**
     * @dataProvider allCasesProvider
     */
    public function testBackedStringValue(InstallStep $step, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $step->value);
    }

    /**
     * @dataProvider allCasesProvider
     */
    public function testFromReturnsCorrectCase(InstallStep $expectedStep, string $value): void
    {
        $this->assertSame($expectedStep, InstallStep::from($value));
    }

    public function testFromThrowsValueErrorForInvalidString(): void
    {
        $this->expectException(\ValueError::class);

        InstallStep::from('nonexistent_step');
    }

    public function testTryFromReturnsNullForInvalidString(): void
    {
        $this->assertNull(InstallStep::tryFrom('nonexistent_step'));
    }

    public function testTryFromReturnsEnumForValidString(): void
    {
        $result = InstallStep::tryFrom('write_env');

        $this->assertSame(InstallStep::WriteEnv, $result);
    }
}
