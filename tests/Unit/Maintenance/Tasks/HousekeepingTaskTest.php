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

    /**
     * Outside the tree being cleaned, so that a symlink target is never itself a candidate
     * for pruning and "the target survived" means something.
     */
    private string $outside;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir() . '/pimcore_housekeeping_test_' . uniqid();
        mkdir($this->root, 0777, true);

        $this->outside = sys_get_temp_dir() . '/pimcore_housekeeping_outside_' . uniqid();
        mkdir($this->outside, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeRecursively($this->root);
        $this->removeRecursively($this->outside);

        parent::tearDown();
    }

    public function testRemovesStaleEmptyDirectoryTreeOneLevelPerRun(): void
    {
        $this->makeDir('stale/a/b');
        $this->age();

        $this->runHousekeeping(seconds: 0, dirSeconds: 0);

        // Removing b is a mutation of a: its mtime/ctime now carry this run's own rmdir(),
        // and the task never prunes a directory it mutated in the same run. So the tree
        // collapses one level per run, deepest level first, rather than all at once.
        $this->assertDirectoryDoesNotExist($this->root . '/stale/a/b', 'the stale leaf should be pruned');
        $this->assertDirectoryExists($this->root . '/stale/a', 'a directory this run emptied must survive the run');

        $this->age();
        $this->runHousekeeping(seconds: 0, dirSeconds: 0);
        $this->assertDirectoryDoesNotExist($this->root . '/stale/a');
        $this->assertDirectoryExists($this->root . '/stale');

        $this->age();
        $this->runHousekeeping(seconds: 0, dirSeconds: 0);
        $this->assertDirectoryDoesNotExist($this->root . '/stale', 'a stale empty tree should be gone once each level has aged out');
        $this->assertDirectoryExists($this->root, 'the folder being cleaned must never be removed');
    }

    public function testKeepsDirectoryYoungerThanCutoff(): void
    {
        $this->makeDir('stale');
        $this->age();
        // Dated an hour ahead rather than left at "now": the cutoff is whole-second time()
        // sampled inside the task, so a directory created in the same second could be
        // read as older than the cutoff if the clock ticked over in between. Setting the
        // mtime is an inode change, so ctime lands on "now" and max(mtime, ctime) is the
        // future value either way.
        touch($this->makeDir('fresh'), time() + 3600);

        $this->runHousekeeping(seconds: 0, dirSeconds: 0);

        $this->assertDirectoryDoesNotExist($this->root . '/stale');
        $this->assertDirectoryExists($this->root . '/fresh', 'a directory created after the cutoff must survive');
    }

    public function testKeepsDirectoryEmptiedByTheSameRun(): void
    {
        $this->makeDir('emptied');
        $this->makeFile('emptied/old.tmp');
        $this->makeFile('emptied/older.tmp');
        $this->age();

        $this->runHousekeeping(seconds: 0, dirSeconds: 0);

        // Deleting the files bumps the directory's mtime/ctime to "now". A stat() taken
        // after that cannot tell the task's own change apart from another process's, so
        // the task does not try: a directory it mutated is never pruned in the same run.
        $this->assertFileDoesNotExist($this->root . '/emptied/old.tmp');
        $this->assertFileDoesNotExist($this->root . '/emptied/older.tmp');
        $this->assertDirectoryExists(
            $this->root . '/emptied',
            'a directory emptied by this run must survive the run that emptied it'
        );
    }

    public function testPrunesDirectoryEmptiedByAnEarlierRunOnceItHasAgedOut(): void
    {
        $this->makeDir('emptied');
        $this->makeFile('emptied/old.tmp');
        $this->age();

        $this->runHousekeeping(seconds: 0, dirSeconds: 0);
        $this->assertDirectoryExists($this->root . '/emptied');

        // The retention applies to the directory itself, not to its former contents: the
        // unlink() above moved its timestamps to "now", so it has to sit untouched for the
        // retention before a later run removes it. Elapsed time is the only way to age a
        // directory - touch() would back-date mtime but bump ctime (see age()).
        $this->age();
        $this->runHousekeeping(seconds: 0, dirSeconds: 0);

        $this->assertDirectoryDoesNotExist(
            $this->root . '/emptied',
            'a directory emptied by an earlier run should be pruned once it has aged past the cutoff'
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

        $this->runHousekeeping(seconds: 0, dirSeconds: 0);

        $this->assertFileDoesNotExist($this->root . '/mixed/old.tmp');
        $this->assertFileExists($this->root . '/mixed/keep.tmp');
        $this->assertDirectoryExists($this->root . '/mixed', 'rmdir() must fail on a non-empty directory');
    }

    public function testDirectoryRetentionIsIndependentOfFileRetention(): void
    {
        // The regression this guards: the temp tree passes a longer directory retention than
        // its file retention, so a directory a request may still be writing into is not pulled
        // out from under it just because the files it already wrote have aged out.
        //
        // The directory has to straddle the two cutoffs for this to prove anything: it is
        // older than the file cutoff (0 seconds) but younger than the directory cutoff, so it
        // survives only because the two retentions are tracked separately. It is left empty
        // on purpose: with a stale file inside, deleting it would mark the directory as
        // mutated by this run, and the directory would then survive for that reason instead.
        // testRemovesStaleEmptyDirectoryTreeOneLevelPerRun shows the same directory is pruned
        // when the directory cutoff is 0.
        $this->makeDir('working');
        $this->age();

        $this->runHousekeeping(seconds: 0, dirSeconds: 86400);

        $this->assertDirectoryExists(
            $this->root . '/working',
            'the directory is younger than the directory cutoff and must be kept'
        );
    }

    public function testKeepsDirectoriesWhenDirectoryPruningIsDisabled(): void
    {
        $this->makeDir('temp_like/nested');
        $this->makeFile('temp_like/nested/old.tmp');
        $this->age();

        $this->runHousekeeping(seconds: 0, dirSeconds: null);

        $this->assertFileDoesNotExist($this->root . '/temp_like/nested/old.tmp');
        $this->assertDirectoryExists(
            $this->root . '/temp_like/nested',
            'directories must be untouched when the caller opts out of directory pruning'
        );
    }

    public function testDirectoryAgeIsTheLatestOfMtimeAndCtime(): void
    {
        // touch() can back-date a directory's mtime, but setting it is itself an inode
        // change, so ctime lands on "now". max(mtime, ctime) therefore keeps this
        // directory; the previous expression, mtime ?: ctime, would read the 30-day-old
        // mtime and prune it. The same asymmetry protects a real directory whose inode
        // changed recently (rename, chmod) while its entries stayed untouched.
        $dir = $this->makeDir('backdated');
        touch($dir, time() - 30 * 86400);
        $this->age();

        $this->runHousekeeping(seconds: 0, dirSeconds: 86400);

        $this->assertDirectoryExists(
            $dir,
            'a directory with an old mtime but a fresh ctime is not stale and must be kept'
        );
    }

    public function testRemovesSymlinkToDirectoryAndLeavesItsTargetAlone(): void
    {
        // isFile() follows the link, so a symlink to a directory reports isFile() === false
        // and looks like a directory to a "not a file" test. It is not one to prune: rmdir()
        // removes only real directories, so recording it alongside them strands it in
        // var/tmp for good. It belongs on the unlink() path that handled it before this tree
        // was walked with a directory cutoff.
        $target = $this->outside . '/target';
        mkdir($target, 0777, true);
        touch($target . '/precious.txt', time() - 7200, time() - 7200);
        symlink($target, $this->root . '/link_to_dir');
        $this->age();

        $this->runHousekeeping(seconds: 0, dirSeconds: 0);

        $this->assertFalse(
            is_link($this->root . '/link_to_dir'),
            'a symlink to a directory must be unlinked, not handed to rmdir()'
        );
        // The walk must not descend through the link: hasChildren() is false for a symlink
        // unless FOLLOW_SYMLINKS is set, so removing it never reaches what it points at.
        $this->assertDirectoryExists($target, 'the link target must be left alone');
        $this->assertFileExists($target . '/precious.txt', 'the walk must not descend through a link');
    }

    public function testRemovesBrokenSymlink(): void
    {
        // A broken link is neither file nor directory, and stat() fails on it - so treating
        // every non-file as a directory parked it in the lookup with no usable time, where
        // it was neither unlinked nor rmdir()'d and simply accumulated.
        symlink($this->root . '/missing', $this->root . '/broken_link');
        $this->age();

        $this->runHousekeeping(seconds: 0, dirSeconds: 0);

        // file_exists() resolves the link, so it reports false either way; is_link() is the
        // only check that distinguishes "removed" from "still dangling".
        $this->assertFalse(is_link($this->root . '/broken_link'), 'a broken symlink must be unlinked');
    }

    public function testKeepsLowQualityImagePreviews(): void
    {
        $this->makeFile('image-low-quality-preview.svg');
        $this->makeFile('-low-quality-preview.svg');
        $this->makeFile('regular.tmp');
        $this->age();

        $this->runHousekeeping(seconds: 0, dirSeconds: 0);

        $this->assertFileExists($this->root . '/image-low-quality-preview.svg');
        // Guards the substring check: a name *starting* with the marker sits at offset 0,
        // which the original strpos() truthiness test treated as "no match".
        $this->assertFileExists($this->root . '/-low-quality-preview.svg');
        $this->assertFileDoesNotExist($this->root . '/regular.tmp');
    }

    private function runHousekeeping(int $seconds, ?int $dirSeconds): void
    {
        $task = new HousekeepingTask(86400, 1800, 604800);

        $method = new ReflectionMethod($task, 'deleteFilesInFolderOlderThanSeconds');
        $method->invoke($task, $this->root, $seconds, $dirSeconds);
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
     * "now". Directory age is max(mtime, ctime), and while touch() can back-date a
     * directory's mtime, doing so bumps its ctime to "now" - so elapsed time is the only
     * way to genuinely age one.
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

            // is_link() first: is_dir() follows the link, so a symlink to a directory would
            // otherwise send this recursion into the target and delete the fixture it points at.
            if (!is_link($child) && is_dir($child)) {
                $this->removeRecursively($child);
            } else {
                @unlink($child);
            }
        }

        @rmdir($path);
    }
}
