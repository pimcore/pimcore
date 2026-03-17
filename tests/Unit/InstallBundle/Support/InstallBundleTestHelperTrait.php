<?php
declare(strict_types=1);

namespace Pimcore\Tests\Unit\InstallBundle\Support;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Shared helpers for InstallBundle unit tests.
 *
 * @internal
 */
trait InstallBundleTestHelperTrait
{
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
