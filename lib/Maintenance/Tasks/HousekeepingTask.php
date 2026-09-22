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

namespace Pimcore\Maintenance\Tasks;

use FilesystemIterator;
use Pimcore\Maintenance\TaskInterface;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @internal
 */
class HousekeepingTask implements TaskInterface
{
    protected int $tmpFileTime;

    protected int $profilerTime;

    protected int $tmpDirectoryTime;

    public function __construct(int $tmpFileTime, int $profilerTime, int $tmpDirectoryTime)
    {
        $this->tmpFileTime = $tmpFileTime;
        $this->profilerTime = $profilerTime;
        $this->tmpDirectoryTime = $tmpDirectoryTime;
    }

    public function execute(): void
    {
        foreach (['dev'] as $environment) {
            $profilerDir = sprintf('%s/%s/profiler', PIMCORE_SYMFONY_CACHE_DIRECTORY, $environment);

            $this->deleteFilesInFolderOlderThanSeconds($profilerDir, $this->profilerTime, $this->profilerTime);
        }

        // Prune empty directories too: without a directory cutoff the system temp tree only
        // ever grows, because rmdir() is never reached. Directories get a retention of their
        // own (cleanup_tmp_directories_older_than, default 7 days) so that a still-in-use
        // working directory is not pulled out from under a request just because the files it
        // already wrote have aged out.
        $this->deleteFilesInFolderOlderThanSeconds(
            PIMCORE_SYSTEM_TEMP_DIRECTORY,
            $this->tmpFileTime,
            $this->tmpDirectoryTime
        );
    }

    /**
     * @param int $seconds retention for files
     * @param ?int $dirSeconds retention for empty directories, null = leave directories alone
     */
    private function deleteFilesInFolderOlderThanSeconds(string $folder, int $seconds, ?int $dirSeconds): void
    {
        if (!is_dir($folder)) {
            return;
        }

        $pruneDirectories = $dirSeconds !== null;
        $now = time();
        $cutoff = $now - $seconds;
        $dirCutoff = $now - ($dirSeconds ?? $seconds);
        $dirTimes = [];

        $directory = new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS);
        $filter = new RecursiveCallbackFilterIterator($directory, function (SplFileInfo $current, $key, $iterator) use ($cutoff, $pruneDirectories, &$dirTimes) {
            if (str_contains($current->getFilename(), '-low-quality-preview.svg') && $current->isFile()) {
                return false;
            }

            if ($current->isFile()) {
                $aTime = $current->getATime();
                $mTime = $current->getMTime();
                $timeToCheck = $aTime ?: $mTime;

                if ($timeToCheck && $timeToCheck < $cutoff) {
                    return true;
                }

                return false;
            }

            // isDir() && !isLink() rather than "not a file": isFile() follows the link, so
            // a symlink to a directory reports isFile() === false, and a broken one is
            // neither file nor directory. Recording either would route it to rmdir(), which
            // removes only real directories - the link would survive every run from here on.
            // Leaving them out of $dirTimes keeps them on the unlink() path that cleaned
            // them up before this tree was walked with a directory cutoff. The walk does not
            // descend through them either: RecursiveDirectoryIterator::hasChildren() is
            // false for a symlink unless FOLLOW_SYMLINKS is set, so removing the link never
            // touches whatever it points at.
            if ($pruneDirectories && $current->isDir() && !$current->isLink()) {
                $path = $current->getPathname();
                // Capture the directory time before its contents are deleted, so a
                // directory that was already stale can be removed in the same run. The
                // filter may run more than once per entry; keep the first (pre-deletion)
                // value. stat() is served from the cache warmed by isFile() above.
                //
                // max(mtime, ctime) is deliberate: mtime moves when an entry is added or
                // removed, ctime when the inode changes (rename, chmod, link count). atime
                // is not used, because on some stacks a plain readdir() bumps it and every
                // directory this task walks would become immortal.
                //
                // dev/ino pin down *which* directory the time belongs to: the removal
                // step below re-checks them, so a path that was removed and recreated by
                // another process in the meantime is not mistaken for the stale one.
                if (!array_key_exists($path, $dirTimes)) {
                    $stat = @stat($path);
                    $dirTimes[$path] = $stat ? [
                        'time' => max($stat['mtime'], $stat['ctime']),
                        'dev' => $stat['dev'],
                        'ino' => $stat['ino'],
                        // max(mtime, ctime) as observed right after this run's own last
                        // unlink()/rmdir() of an entry; null while this run has not touched it
                        'selfTime' => null,
                    ] : false;
                }
            }

            return true;
        });

        $mode = $pruneDirectories ? RecursiveIteratorIterator::CHILD_FIRST : RecursiveIteratorIterator::LEAVES_ONLY;
        // CATCH_GET_CHILD: a directory can vanish between the parent's readdir and the
        // attempt to descend into it - either because this run just removed it, or
        // because another node did. On NFS the parent's cached listing makes that
        // routine. Without this flag the UnexpectedValueException aborts the whole
        // walk and the job dies partway; with it, the subtree is skipped and picked
        // up on the next run.
        $iterator = new RecursiveIteratorIterator($filter, $mode, RecursiveIteratorIterator::CATCH_GET_CHILD);

        // Record the parent's timestamp as it stands right after this run's own
        // unlink()/rmdir() of one of its entries. The freshness re-check below waives
        // exactly that value - and nothing later - so a change made by someone else
        // after this run's last mutation still counts as activity. Overwritten on
        // every mutation, so it always holds the last self-generated value.
        $recordOwnChange = static function (string $path) use (&$dirTimes): void {
            $parent = dirname($path);
            if (!isset($dirTimes[$parent]) || !is_array($dirTimes[$parent])) {
                return;
            }
            clearstatcache(true, $parent);
            $stat = @stat($parent);
            if ($stat) {
                $dirTimes[$parent]['selfTime'] = max($stat['mtime'], $stat['ctime']);
            }
        };

        foreach ($iterator as $entry) {
            $path = $entry->getPathname();

            if (array_key_exists($path, $dirTimes)) {
                $dir = $dirTimes[$path];
                // Drop the entry as soon as it is consumed. CHILD_FIRST only yields a
                // directory once its whole subtree has been walked, so the live set stays
                // proportional to the tree depth instead of the tree size (~25MB at 95k
                // directories).
                unset($dirTimes[$path]);

                if (!$dir || $dir['time'] >= $dirCutoff) {
                    continue;
                }

                // The recorded time was read while filtering, and the whole subtree walk
                // sits between that and this point. Another process may have removed and
                // recreated the path in that window; rmdir()'ing the replacement would
                // bypass the retention and reopen the mkdir-before-write race this
                // retention exists to close. So re-stat immediately before removing and
                // require the same inode. Then re-apply the freshness rule:
                //
                //  - if this run never touched the directory (selfTime === null), it is
                //    kept when its current time has reached the cutoff - something else
                //    touched it in the window and it is in use;
                //  - if this run did unlink()/rmdir() entries from it, the current time is
                //    expected to be the one recorded right after the last of those, so
                //    only a time *later* than that is treated as foreign activity. Waiving
                //    the exact self-generated value rather than the whole check means a
                //    chmod, or a create/remove pair, that lands after this run's last
                //    mutation still keeps the directory.
                //
                // Limitation: timestamps are compared at the filesystem's granularity
                // (one second on many filesystems), so a foreign change that lands in the
                // same second as this run's own last mutation is indistinguishable from
                // it and does not keep the directory. rmdir() still refuses a directory
                // that is not empty, so only an empty directory can be lost to that.
                clearstatcache(true, $path);
                $fresh = @stat($path);
                if (!$fresh || $fresh['dev'] !== $dir['dev'] || $fresh['ino'] !== $dir['ino']) {
                    continue;
                }
                $freshTime = max($fresh['mtime'], $fresh['ctime']);
                if ($dir['selfTime'] === null ? $freshTime >= $dirCutoff : $freshTime > $dir['selfTime']) {
                    continue;
                }

                // rmdir() is atomic: the kernel checks emptiness and removes in one
                // operation, avoiding the TOCTOU race of a separate is_dir_empty() call.
                if (@rmdir($path)) {
                    $recordOwnChange($path);
                }
            } elseif (@unlink($path)) {
                $recordOwnChange($path);
            }
        }
    }
}
