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

namespace Pimcore\Tests\Unit\Maintenance\Tasks;

use Pimcore\Maintenance\Tasks\HousekeepingTask;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionMethod;

/**
 * @internal
 */
final class HousekeepingTaskTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir() . '/pimcore_housekeeping_test_' . uniqid();
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeRecursively($this->root);

        parent::tearDown();
    }

    public function testRemovesStaleEmptyDirectoryTree(): void
    {
        $this->makeDir('stale/a/b');
        $this->age();

        $this->run(seconds: 0, clearFolder: true);

        $this->assertDirectoryDoesNotExist($this->root . '/stale', 'a stale empty tree should be pruned');
        $this->assertDirectoryExists($this->root, 'the folder being cleaned must never be removed');
    }

    public function testKeepsDirectoryYoungerThanCutoff(): void
    {
        $this->makeDir('stale');
        $this->age();
        $this->makeDir('fresh');

        $this->run(seconds: 0, clearFolder: true);

        $this->assertDirectoryDoesNotExist($this->root . '/stale');
        $this->assertDirectoryExists($this->root . '/fresh', 'a directory created after the cutoff must survive');
    }

    public function testPrunesDirectoryEmptiedByTheSameRun(): void
    {
        $this->makeDir('emptied');
        $this->makeFile('emptied/old.tmp');
        $this->makeFile('emptied/older.tmp');
        $this->age();

        $this->run(seconds: 0, clearFolder: true);

        // Deleting the files bumps the directory's mtime/ctime to "now". The directory time
        // is captured in the filter callback before that happens, so the directory is still
        // pruned in this run rather than waiting for the next one.
        $this->assertDirectoryDoesNotExist(
            $this->root . '/emptied',
            'a stale directory emptied by this run should be pruned in the same run'
        );
    }

    public function testKeepsDirectoryThatStillHasAFreshFile(): void
    {
        // Both files are created before the sleep so the directory's own timestamps stay old
        // and it remains a pruning candidate. keep.tmp is only then re-dated into the future:
        // re-timestamping an existing file leaves the directory entry, and so the directory's
        // mtime, untouched. Creating it after the sleep instead would freshen the directory
        // and the assertion below would hold for the wrong reason.
        $this->makeDir('mixed');
        $this->makeFile('mixed/old.tmp', age: 7200);
        $this->makeFile('mixed/keep.tmp');
        $this->age();
        $this->makeFile('mixed/keep.tmp', age: -3600);

        $this->run(seconds: 0, clearFolder: true);

        $this->assertFileDoesNotExist($this->root . '/mixed/old.tmp');
        $this->assertFileExists($this->root . '/mixed/keep.tmp');
        $this->assertDirectoryExists($this->root . '/mixed', 'rmdir() must fail on a non-empty directory');
    }

    public function testKeepsDirectoriesWhenClearFolderIsDisabled(): void
    {
        $this->makeDir('temp_like/nested');
        $this->makeFile('temp_like/nested/old.tmp');
        $this->age();

        $this->run(seconds: 0, clearFolder: false);

        $this->assertFileDoesNotExist($this->root . '/temp_like/nested/old.tmp');
        $this->assertDirectoryExists(
            $this->root . '/temp_like/nested',
            'directories must be untouched when the caller opts out of folder clearing'
        );
    }

    public function testKeepsLowQualityImagePreviews(): void
    {
        $this->makeFile('image-low-quality-preview.svg');
        $this->makeFile('-low-quality-preview.svg');
        $this->makeFile('regular.tmp');
        $this->age();

        $this->run(seconds: 0, clearFolder: true);

        $this->assertFileExists($this->root . '/image-low-quality-preview.svg');
        // Guards the substring check: a name *starting* with the marker sits at offset 0,
        // which the original strpos() truthiness test treated as "no match".
        $this->assertFileExists($this->root . '/-low-quality-preview.svg');
        $this->assertFileDoesNotExist($this->root . '/regular.tmp');
    }

    private function run(int $seconds, bool $clearFolder): void
    {
        $task = new HousekeepingTask(86400, 1800);

        $method = new ReflectionMethod($task, 'deleteFilesInFolderOlderThanSeconds');
        $method->invoke($task, $this->root, $seconds, $clearFolder);
    }

    private function makeDir(string $relativePath): string
    {
        $path = $this->root . '/' . $relativePath;
        mkdir($path, 0777, true);

        return $path;
    }

    private function makeFile(string $relativePath, int $age = 7200): string
    {
        $path = $this->root . '/' . $relativePath;
        touch($path, time() - $age, time() - $age);

        return $path;
    }

    /**
     * Let real time pass so the entries created so far are strictly older than a cutoff of
     * "now". Directory age is derived from the directory's own timestamps, which cannot be
     * back-dated by touch() the way a file's can, so elapsed time is the only way to age one.
     */
    private function age(): void
    {
        sleep(1);
    }

    private function removeRecursively(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $child = $path . '/' . $entry;
            is_dir($child) ? $this->removeRecursively($child) : @unlink($child);
        }

        @rmdir($path);
    }
}
