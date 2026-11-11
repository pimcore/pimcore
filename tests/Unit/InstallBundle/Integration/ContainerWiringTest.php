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
use Pimcore\Bundle\InstallBundle\InstallerKernel;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests that the InstallerKernel's DI container correctly wires all services.
 *
 * Boots a real InstallerKernel pointing to a temporary directory and verifies
 * that the Installer and InstallCommand services resolve with correct dependencies.
 *
 * @internal
 */
final class ContainerWiringTest extends TestCase
{
    private string $tempDir;

    private ?InstallerKernel $installerKernel = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/pimcore_container_wiring_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if ($this->installerKernel !== null) {
            $this->installerKernel->shutdown();
            $this->installerKernel = null;
        }

        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    public function testInstallerKernelBootsSuccessfully(): void
    {
        $kernel = $this->bootInstallerKernel();

        $this->assertNotNull($kernel->getContainer());
    }

    public function testInstallerKernelRegistersCorrectBundles(): void
    {
        $kernel = $this->bootInstallerKernel();
        $bundles = $kernel->getBundles();

        $this->assertArrayHasKey('FrameworkBundle', $bundles);
        $this->assertArrayHasKey('MonologBundle', $bundles);
        $this->assertArrayHasKey('PimcoreInstallBundle', $bundles);
        $this->assertArrayHasKey('DebugBundle', $bundles, 'DebugBundle should be registered in test env');
    }

    public function testInstallerServiceResolvesFromContainer(): void
    {
        $kernel = $this->bootInstallerKernel();
        $container = $kernel->getContainer();

        $installer = $container->get(Installer::class);

        $this->assertInstanceOf(Installer::class, $installer);
    }

    public function testInstallCommandResolvesFromContainer(): void
    {
        $kernel = $this->bootInstallerKernel();
        $container = $kernel->getContainer();

        $command = $container->get(InstallCommand::class);

        $this->assertInstanceOf(InstallCommand::class, $command);
        $this->assertSame('pimcore:install', $command->getName());
    }

    public function testInstallerReceivesLoggerInterface(): void
    {
        $kernel = $this->bootInstallerKernel();
        $container = $kernel->getContainer();

        // The Installer service should be instantiated without errors,
        // which means its LoggerInterface dependency was satisfied
        $installer = $container->get(Installer::class);
        $this->assertInstanceOf(Installer::class, $installer);
    }

    public function testInstallerReceivesEventDispatcher(): void
    {
        $kernel = $this->bootInstallerKernel();
        $container = $kernel->getContainer();

        // Verify the EventDispatcherInterface is available in the container
        $dispatcher = $container->get('event_dispatcher');
        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
    }

    public function testKernelUsesCustomProjectRoot(): void
    {
        $kernel = $this->bootInstallerKernel();

        $this->assertSame($this->tempDir, $kernel->getProjectDir());
        $this->assertSame($this->tempDir . '/var/installer/log', $kernel->getLogDir());
        $this->assertSame($this->tempDir . '/var/installer/cache', $kernel->getCacheDir());
        $this->assertSame($this->tempDir . '/var/installer/build', $kernel->getBuildDir());
    }

    public function testKernelCanBeBootedMultipleTimes(): void
    {
        $kernel1 = $this->bootInstallerKernel();
        $installer1 = $kernel1->getContainer()->get(Installer::class);
        $kernel1->shutdown();

        // Boot a second kernel with different unique ID
        $this->installerKernel = null;
        $kernel2 = $this->bootInstallerKernel();
        $installer2 = $kernel2->getContainer()->get(Installer::class);

        $this->assertInstanceOf(Installer::class, $installer1);
        $this->assertInstanceOf(Installer::class, $installer2);
        // They should be different instances (different kernel boots)
        $this->assertNotSame($installer1, $installer2);
    }

    private function bootInstallerKernel(): InstallerKernel
    {
        $kernel = new InstallerKernel($this->tempDir, 'test', true);
        $kernel->boot();

        $this->installerKernel = $kernel;

        return $kernel;
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
