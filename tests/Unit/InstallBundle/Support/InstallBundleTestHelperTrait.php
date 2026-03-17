<?php
declare(strict_types=1);

namespace Pimcore\Tests\Unit\InstallBundle\Support;

use Pimcore\Bundle\InstallBundle\BundleConfig\BundleInstaller;
use Pimcore\Bundle\InstallBundle\Console\ConsoleCommandRunner;
use Pimcore\Bundle\InstallBundle\Database\DatabaseSetup;
use Pimcore\Bundle\InstallBundle\DefinitionResolver;
use Pimcore\Bundle\InstallBundle\Installer;
use Pimcore\Bundle\InstallBundle\PostInstall\PostInstallRunner;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Shared helpers for InstallBundle unit tests.
 *
 * @internal
 */
trait InstallBundleTestHelperTrait
{
    /**
     * Create a fully-wired Installer with all required dependencies.
     *
     * @param (\Closure(string): \Pimcore\Bundle\InstallBundle\Checkpoint\InstallerCheckpoint)|null $checkpointFactory
     * @param (\Closure(string): \Pimcore\Bundle\InstallBundle\Env\EnvWriter)|null $envWriterFactory
     */
    protected function createInstaller(
        ?LoggerInterface $logger = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?\Closure $checkpointFactory = null,
        ?\Closure $envWriterFactory = null,
    ): Installer {
        $logger ??= new NullLogger();
        $commandRunner = new ConsoleCommandRunner($logger);

        return new Installer(
            $logger,
            $eventDispatcher ?? new EventDispatcher(),
            new DatabaseSetup(),
            new DefinitionResolver(),
            $commandRunner,
            new BundleInstaller($logger, $commandRunner),
            new PostInstallRunner($logger, $commandRunner),
            $checkpointFactory,
            $envWriterFactory,
        );
    }

    protected function removeDirectory(string $dir): void
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

    protected function createNonInteractiveIo(): SymfonyStyle
    {
        return new SymfonyStyle(
            new ArrayInput([]),
            new NullOutput(),
        );
    }
}
