<?php
declare(strict_types=1);

namespace Pimcore\Tests\Unit\InstallBundle\Database;

use Pimcore\Bundle\InstallBundle\Database\DatabaseSetup;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * @internal
 */
final class DatabaseSetupTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $setup = new DatabaseSetup();
        $this->assertInstanceOf(DatabaseSetup::class, $setup);
    }

    public function testInstallSqlFileExists(): void
    {
        $installSqlPath = __DIR__ . '/../../../../bundles/InstallBundle/dump/install.sql';
        $this->assertFileExists($installSqlPath, 'install.sql must exist for DatabaseSetup to work');
    }
}
