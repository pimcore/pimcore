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

namespace Pimcore\Tests\Unit\Asset\StorageQueue;

use Codeception\Test\Unit;
use DateTimeImmutable;
use FilesystemIterator;
use League\Flysystem\ChecksumProvider;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnableToGeneratePublicUrl;
use League\Flysystem\UnableToGenerateTemporaryUrl;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use LogicException;
use Pimcore\Asset\StorageQueue\QueueAwareStorageAdapter;
use Pimcore\Asset\StorageQueue\StorageOperation;
use Pimcore\Asset\StorageQueue\StorageOperationType;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class QueueAwareStorageAdapterTest extends Unit
{
    private string $tmpDir;

    private InMemoryStorageOperationQueueRepository $repository;

    protected function _before(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/queue-adapter-test-' . uniqid();
        mkdir($this->tmpDir, 0777, true);
        $this->repository = new InMemoryStorageOperationQueueRepository();
    }

    protected function _after(): void
    {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tmpDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->tmpDir);
    }

    private function adapter(): QueueAwareStorageAdapter
    {
        return new QueueAwareStorageAdapter(
            new LocalFilesystemAdapter($this->tmpDir),
            $this->repository,
            'asset',
        );
    }

    public function testDelegatesBasicOperationsWithEmptyQueue(): void
    {
        $adapter = $this->adapter();
        $adapter->write('folder/file.txt', 'content', new Config());

        $this->assertTrue($adapter->fileExists('folder/file.txt'));
        $this->assertTrue($adapter->directoryExists('folder'));
        $this->assertSame('content', $adapter->read('folder/file.txt'));
        $this->assertSame('content', stream_get_contents($adapter->readStream('folder/file.txt')));
        $this->assertGreaterThan(0, $adapter->fileSize('folder/file.txt')->fileSize());
        $this->assertNotNull($adapter->lastModified('folder/file.txt')->lastModified());
        $this->assertSame('text/plain', $adapter->mimeType('folder/file.txt')->mimeType());

        $adapter->copy('folder/file.txt', 'folder/copy.txt', new Config());
        $this->assertTrue($adapter->fileExists('folder/copy.txt'));

        $adapter->move('folder/copy.txt', 'folder/moved.txt', new Config());
        $this->assertTrue($adapter->fileExists('folder/moved.txt'));
        $this->assertFalse($adapter->fileExists('folder/copy.txt'));

        $adapter->delete('folder/moved.txt');
        $this->assertFalse($adapter->fileExists('folder/moved.txt'));

        $adapter->createDirectory('newdir', new Config());
        $this->assertTrue($adapter->directoryExists('newdir'));

        $paths = [];
        foreach ($adapter->listContents('folder', true) as $item) {
            $paths[] = $item->path();
        }
        $this->assertSame(['folder/file.txt'], $paths);

        $this->assertSame([], $this->repository->all(), 'no queue rows for single-file operations');
    }

    public function testPublicUrlThrowsWhenInnerDoesNotSupportIt(): void
    {
        // LocalFilesystemAdapter without a prefixer implements none of the URL interfaces
        $this->expectException(UnableToGeneratePublicUrl::class);

        $this->adapter()->publicUrl('folder/file.txt', new Config());
    }

    public function testTemporaryUrlThrowsWhenInnerDoesNotSupportIt(): void
    {
        // LocalFilesystemAdapter implements none of the URL interfaces
        $this->expectException(UnableToGenerateTemporaryUrl::class);

        $this->adapter()->temporaryUrl('folder/file.txt', new DateTimeImmutable(), new Config());
    }

    public function testChecksumFallsBackToStreamHashWhenInnerIsNotAChecksumProvider(): void
    {
        // NB: LocalFilesystemAdapter itself implements ChecksumProvider, so it can't be used to
        // exercise the fallback branch. This inner only implements FilesystemAdapter (delegating
        // to a real Local adapter for actual file I/O), which forces QueueAwareStorageAdapter
        // into CalculateChecksumFromStream.
        $inner = new class(new LocalFilesystemAdapter($this->tmpDir)) implements FilesystemAdapter {
            public function __construct(private readonly FilesystemAdapter $local)
            {
            }

            public function fileExists(string $path): bool
            {
                return $this->local->fileExists($path);
            }

            public function directoryExists(string $path): bool
            {
                return $this->local->directoryExists($path);
            }

            public function write(string $path, string $contents, Config $config): void
            {
                $this->local->write($path, $contents, $config);
            }

            public function writeStream(string $path, $contents, Config $config): void
            {
                $this->local->writeStream($path, $contents, $config);
            }

            public function read(string $path): string
            {
                return $this->local->read($path);
            }

            public function readStream(string $path)
            {
                return $this->local->readStream($path);
            }

            public function delete(string $path): void
            {
                $this->local->delete($path);
            }

            public function deleteDirectory(string $path): void
            {
                $this->local->deleteDirectory($path);
            }

            public function createDirectory(string $path, Config $config): void
            {
                $this->local->createDirectory($path, $config);
            }

            public function setVisibility(string $path, string $visibility): void
            {
                $this->local->setVisibility($path, $visibility);
            }

            public function visibility(string $path): FileAttributes
            {
                return $this->local->visibility($path);
            }

            public function mimeType(string $path): FileAttributes
            {
                return $this->local->mimeType($path);
            }

            public function lastModified(string $path): FileAttributes
            {
                return $this->local->lastModified($path);
            }

            public function fileSize(string $path): FileAttributes
            {
                return $this->local->fileSize($path);
            }

            public function listContents(string $path, bool $deep): iterable
            {
                return $this->local->listContents($path, $deep);
            }

            public function move(string $source, string $destination, Config $config): void
            {
                $this->local->move($source, $destination, $config);
            }

            public function copy(string $source, string $destination, Config $config): void
            {
                $this->local->copy($source, $destination, $config);
            }
        };

        $adapter = new QueueAwareStorageAdapter($inner, $this->repository, 'asset');
        $adapter->write('folder/file.txt', 'content', new Config());

        $this->assertSame(md5('content'), $adapter->checksum('folder/file.txt', new Config()));
    }

    public function testChecksumDelegatesToInnerWhenItIsAChecksumProvider(): void
    {
        $inner = new class implements FilesystemAdapter, ChecksumProvider {
            private ?string $receivedPath = null;

            public function checksum(string $path, Config $config): string
            {
                $this->receivedPath = $path;

                return 'sentinel-checksum';
            }

            public function getReceivedPath(): ?string
            {
                return $this->receivedPath;
            }

            public function fileExists(string $path): bool
            {
                throw new LogicException('not implemented in stub');
            }

            public function directoryExists(string $path): bool
            {
                throw new LogicException('not implemented in stub');
            }

            public function write(string $path, string $contents, Config $config): void
            {
                throw new LogicException('not implemented in stub');
            }

            public function writeStream(string $path, $contents, Config $config): void
            {
                throw new LogicException('not implemented in stub');
            }

            public function read(string $path): string
            {
                throw new LogicException('not implemented in stub');
            }

            public function readStream(string $path)
            {
                throw new LogicException('not implemented in stub');
            }

            public function delete(string $path): void
            {
                throw new LogicException('not implemented in stub');
            }

            public function deleteDirectory(string $path): void
            {
                throw new LogicException('not implemented in stub');
            }

            public function createDirectory(string $path, Config $config): void
            {
                throw new LogicException('not implemented in stub');
            }

            public function setVisibility(string $path, string $visibility): void
            {
                throw new LogicException('not implemented in stub');
            }

            public function visibility(string $path): FileAttributes
            {
                throw new LogicException('not implemented in stub');
            }

            public function mimeType(string $path): FileAttributes
            {
                throw new LogicException('not implemented in stub');
            }

            public function lastModified(string $path): FileAttributes
            {
                throw new LogicException('not implemented in stub');
            }

            public function fileSize(string $path): FileAttributes
            {
                throw new LogicException('not implemented in stub');
            }

            public function listContents(string $path, bool $deep): iterable
            {
                throw new LogicException('not implemented in stub');
            }

            public function move(string $source, string $destination, Config $config): void
            {
                throw new LogicException('not implemented in stub');
            }

            public function copy(string $source, string $destination, Config $config): void
            {
                throw new LogicException('not implemented in stub');
            }
        };

        $adapter = new QueueAwareStorageAdapter($inner, $this->repository, 'asset');

        $this->assertSame('sentinel-checksum', $adapter->checksum('folder/file.txt', new Config()));
        $this->assertSame('folder/file.txt', $inner->getReceivedPath());
    }

    private function addMove(string $source, string $target): void
    {
        $this->repository->add(new StorageOperation(
            null, 'asset', StorageOperationType::Move, $source, $target, new DateTimeImmutable()
        ));
    }

    public function testReadsResolveThroughPendingMove(): void
    {
        $adapter = $this->adapter();
        // physical file still at legacy location, logical path already moved
        $adapter->write('Campaigns/img.jpg', 'legacy-bytes', new Config());
        $this->addMove('Campaigns', 'Archive/Campaigns');

        $this->assertTrue($adapter->fileExists('Archive/Campaigns/img.jpg'));
        $this->assertSame('legacy-bytes', $adapter->read('Archive/Campaigns/img.jpg'));
        $this->assertTrue($adapter->directoryExists('Archive/Campaigns'));
        $this->assertSame('legacy-bytes', stream_get_contents($adapter->readStream('Archive/Campaigns/img.jpg')));
    }

    public function testLiteralWinsOverMappedCandidate(): void
    {
        $adapter = $this->adapter();
        $adapter->write('Campaigns/img.jpg', 'legacy-bytes', new Config());
        $this->addMove('Campaigns', 'Archive/Campaigns');
        // a fresh upload after the move lands literally and shadows the legacy object
        $adapter->write('Archive/Campaigns/img.jpg', 'new-bytes', new Config());

        $this->assertSame('new-bytes', $adapter->read('Archive/Campaigns/img.jpg'));
    }

    public function testDeleteRemovesTheMappedObject(): void
    {
        $adapter = $this->adapter();
        $adapter->write('Campaigns/img.jpg', 'legacy-bytes', new Config());
        $this->addMove('Campaigns', 'Archive/Campaigns');

        $adapter->delete('Archive/Campaigns/img.jpg');

        $this->assertFalse($adapter->fileExists('Archive/Campaigns/img.jpg'));
        $this->assertFalse($this->adapter()->fileExists('Campaigns/img.jpg'), 'legacy object gone');
    }

    public function testSingleFileMoveOutOfPendingSubtreeUsesMappedSource(): void
    {
        $adapter = $this->adapter();
        $adapter->write('Campaigns/img.jpg', 'legacy-bytes', new Config());
        $this->addMove('Campaigns', 'Archive/Campaigns');

        $adapter->move('Archive/Campaigns/img.jpg', 'Elsewhere/img.jpg', new Config());

        $this->assertSame('legacy-bytes', $adapter->read('Elsewhere/img.jpg'));
        $this->assertFalse($adapter->fileExists('Archive/Campaigns/img.jpg'));
        $this->assertCount(1, $this->repository->all(), 'no additional row for a single-file move');
    }

    public function testMultibytePrefixResolution(): void
    {
        $adapter = $this->adapter();
        $adapter->write('Küchengeräte/Öfen/ü.jpg', 'bytes', new Config());
        $this->addMove('Küchengeräte', 'Archiv/Küchengeräte');

        $this->assertSame('bytes', $adapter->read('Archiv/Küchengeräte/Öfen/ü.jpg'));
    }

    public function testReMoveResolvesThroughFlatCandidates(): void
    {
        $adapter = $this->adapter();
        $adapter->write('A/img.jpg', 'from-a', new Config());
        $this->addMove('A', 'B');
        // file uploaded while A->B pending, lands literally under B
        $adapter->write('B/fresh.jpg', 'from-b', new Config());
        $this->addMove('B', 'C'); // fake repoints A->C, adds B->C

        $this->assertSame('from-a', $adapter->read('C/img.jpg'));
        $this->assertSame('from-b', $adapter->read('C/fresh.jpg'));
    }

    private function nonRenamingAdapter(): QueueAwareStorageAdapter
    {
        return new QueueAwareStorageAdapter(
            new NonRenamingAdapterDecorator(new LocalFilesystemAdapter($this->tmpDir)),
            $this->repository,
            'asset',
        );
    }

    public function testPrefixMoveOnRenamingBackendStaysNative(): void
    {
        $adapter = $this->adapter(); // local: native directory rename works
        $adapter->write('Campaigns/img.jpg', 'bytes', new Config());

        $adapter->move('Campaigns', 'Archive', new Config());

        $this->assertTrue($adapter->fileExists('Archive/img.jpg'));
        $this->assertFalse($adapter->directoryExists('Campaigns'));
        $this->assertSame([], $this->repository->all(), 'native rename produced no queue row');
    }

    public function testPrefixMoveOnNonRenamingBackendQueuesARow(): void
    {
        $adapter = $this->nonRenamingAdapter();
        $adapter->write('Campaigns/img.jpg', 'bytes', new Config());

        $adapter->move('Campaigns', 'Archive/Campaigns', new Config());

        $this->assertTrue($adapter->fileExists('Campaigns/img.jpg'), 'physical object untouched');
        $operations = $this->repository->all();
        $this->assertCount(1, $operations);
        $this->assertSame('move', $operations[0]->getType()->value);
        $this->assertSame('Campaigns', $operations[0]->getSourcePrefix());
        $this->assertSame('Archive/Campaigns', $operations[0]->getTargetPrefix());
        // and the logical view is already correct:
        $this->assertSame('bytes', $adapter->read('Archive/Campaigns/img.jpg'));
    }

    public function testMovingAnEmptyFolderThrowsAndQueuesNothing(): void
    {
        $adapter = $this->nonRenamingAdapter();

        try {
            $adapter->move('DoesNotExist', 'Elsewhere', new Config());
            $this->fail('expected UnableToMoveFile');
        } catch (UnableToMoveFile) {
        }
        $this->assertSame([], $this->repository->all());
    }

    public function testReMoveWithNativeRenameStillRepointsPendingRows(): void
    {
        // A -> B pending (objects at A); fresh literal upload under B; then B -> C where the
        // backend CAN rename: literal B must be renamed AND the pending row must be repointed.
        $adapter = $this->adapter(); // local, renaming
        $adapter->write('A/img.jpg', 'from-a', new Config());
        $this->addMove('A', 'B');
        $adapter->write('B/fresh.jpg', 'from-b', new Config());

        $adapter->move('B', 'C', new Config());

        $this->assertSame('from-b', $adapter->read('C/fresh.jpg'), 'literal content renamed natively');
        $this->assertSame('from-a', $adapter->read('C/img.jpg'), 'legacy content reachable via repointed row');
        $pairs = array_map(
            static fn ($op) => $op->getSourcePrefix() . '->' . ($op->getTargetPrefix() ?? ''),
            $this->repository->all()
        );
        sort($pairs);
        $this->assertSame(['A->C'], $pairs, 'native rename must not insert a vacuous B->C row');
    }

    public function testNativeReMoveDoesNotShadowRecreatedSourceFolder(): void
    {
        // A -> B pending; B has literal content so the rename is native; after B is renamed to
        // C, B is re-created from scratch. A vacuous B->C row would wrongly serve B's fresh
        // content through C.
        $adapter = $this->adapter(); // local, renaming
        $adapter->write('A/img.jpg', 'from-a', new Config());
        $this->addMove('A', 'B');
        $adapter->write('B/fresh.jpg', 'from-b', new Config()); // gives B a literal presence

        $adapter->move('B', 'C', new Config());

        // B is re-created after the move - must not leak through a vacuous B->C row
        $adapter->write('B/new-era.jpg', 'new', new Config());

        try {
            $adapter->read('C/new-era.jpg');
            $this->fail('expected UnableToReadFile: no vacuous row should shadow the re-created folder');
        } catch (UnableToReadFile) {
        }
        $this->assertFalse($adapter->fileExists('C/new-era.jpg'));

        $pairs = array_map(
            static fn ($op) => $op->getSourcePrefix() . '->' . ($op->getTargetPrefix() ?? ''),
            $this->repository->all()
        );
        $this->assertSame(['A->C'], $pairs);
    }

    public function testDeleteDirectoryQueuesATombstone(): void
    {
        $adapter = $this->nonRenamingAdapter();
        $adapter->write('Campaigns/img.jpg', 'bytes', new Config());

        $adapter->deleteDirectory('Campaigns');

        $this->assertTrue($adapter->fileExists('Campaigns/img.jpg'), 'physical delete deferred');
        $operations = $this->repository->all();
        $this->assertCount(1, $operations);
        $this->assertSame('delete', $operations[0]->getType()->value);
        $this->assertSame('Campaigns', $operations[0]->getSourcePrefix());
        $this->assertNull($operations[0]->getTargetPrefix());
    }

    public function testDeleteDirectoryOfNothingDelegates(): void
    {
        $adapter = $this->nonRenamingAdapter();

        $adapter->deleteDirectory('DoesNotExist'); // Flysystem contract: no-op success

        $this->assertSame([], $this->repository->all());
    }

    public function testDeleteDirectoryOfPendingMoveTargetTombstonesTheLegacyPrefix(): void
    {
        $adapter = $this->nonRenamingAdapter();
        $adapter->write('A/img.jpg', 'bytes', new Config());
        $this->addMove('A', 'B');

        $adapter->deleteDirectory('B');

        $types = array_map(static fn ($op) => $op->getType()->value . ':' . $op->getSourcePrefix(), $this->repository->all());
        sort($types);
        $this->assertSame(['delete:A', 'delete:B'], $types);
    }

    public function testListContentsMergesLiteralAndMappedEntries(): void
    {
        $adapter = $this->nonRenamingAdapter();
        $adapter->write('Campaigns/legacy.jpg', 'a', new Config());
        $adapter->write('Campaigns/shadowed.jpg', 'legacy-version', new Config());
        $this->addMove('Campaigns', 'Archive/Campaigns');
        $adapter->write('Archive/Campaigns/fresh.jpg', 'b', new Config());
        $adapter->write('Archive/Campaigns/shadowed.jpg', 'new-version', new Config());

        $paths = [];
        foreach ($adapter->listContents('Archive/Campaigns', false) as $item) {
            $paths[] = $item->path();
        }
        sort($paths);
        $this->assertSame(
            ['Archive/Campaigns/fresh.jpg', 'Archive/Campaigns/legacy.jpg', 'Archive/Campaigns/shadowed.jpg'],
            $paths,
            'merged, translated to logical paths, de-duplicated (literal wins)'
        );
    }

    public function testDeepListContentsTranslatesMappedPaths(): void
    {
        $adapter = $this->nonRenamingAdapter();
        $adapter->write('Campaigns/sub/deep.jpg', 'x', new Config());
        $this->addMove('Campaigns', 'Archive/Campaigns');

        $paths = [];
        foreach ($adapter->listContents('Archive/Campaigns', true) as $item) {
            if ($item->isFile()) {
                $paths[] = $item->path();
            }
        }
        $this->assertSame(['Archive/Campaigns/sub/deep.jpg'], $paths);
    }

    public function testPrefixMoveOnRegeneratingStorageTombstonesInsteadOfQueueingMove(): void
    {
        $adapter = new QueueAwareStorageAdapter(
            new NonRenamingAdapterDecorator(new LocalFilesystemAdapter($this->tmpDir)),
            $this->repository,
            'thumbnail',
            true,
        );
        $adapter->write('Camp/img.jpg', 'bytes', new Config());

        $adapter->move('Camp', 'Arch/Camp', new Config());

        $operations = $this->repository->all();
        $this->assertCount(1, $operations);
        $this->assertSame('delete', $operations[0]->getType()->value);
        $this->assertSame('thumbnail', $operations[0]->getStorage());
        $this->assertSame('Camp', $operations[0]->getSourcePrefix());
        $this->assertNull($operations[0]->getTargetPrefix());

        $this->assertFalse($adapter->fileExists('Arch/Camp/img.jpg'), 'nothing moved, nothing mapped');
        $this->assertTrue(
            (new LocalFilesystemAdapter($this->tmpDir))->fileExists('Camp/img.jpg'),
            'the literal source object still physically exists - swept later by the processor'
        );
    }

    public function testPrefixMoveOnRegeneratingStorageStillSkipsEmptyFolder(): void
    {
        $adapter = new QueueAwareStorageAdapter(
            new NonRenamingAdapterDecorator(new LocalFilesystemAdapter($this->tmpDir)),
            $this->repository,
            'thumbnail',
            true,
        );

        try {
            $adapter->move('DoesNotExist', 'Elsewhere', new Config());
            $this->fail('expected UnableToMoveFile');
        } catch (UnableToMoveFile) {
        }
        $this->assertSame([], $this->repository->all());
    }

    public function testNativeRenameOnRegeneratingStorageMovesWithoutRow(): void
    {
        $adapter = new QueueAwareStorageAdapter(
            new LocalFilesystemAdapter($this->tmpDir),
            $this->repository,
            'thumbnail',
            true,
        );
        $adapter->write('Camp/img.jpg', 'bytes', new Config());

        $adapter->move('Camp', 'Arch', new Config());

        $this->assertTrue($adapter->fileExists('Arch/img.jpg'), 'thumbnails preserved for free on renaming backends');
        $this->assertFalse($adapter->directoryExists('Camp'));
        $this->assertSame([], $this->repository->all(), 'native rename produced no queue row');
    }

    public function testDefaultFlagKeepsMoveRowBehavior(): void
    {
        // 3-arg constructor (flag omitted) must keep the pre-existing Move-row fallback
        // behavior on a non-renaming backend, unchanged.
        $adapter = new QueueAwareStorageAdapter(
            new NonRenamingAdapterDecorator(new LocalFilesystemAdapter($this->tmpDir)),
            $this->repository,
            'asset',
        );
        $adapter->write('Campaigns/img.jpg', 'bytes', new Config());

        $adapter->move('Campaigns', 'Archive/Campaigns', new Config());

        $this->assertTrue($adapter->fileExists('Campaigns/img.jpg'), 'physical object untouched');
        $operations = $this->repository->all();
        $this->assertCount(1, $operations);
        $this->assertSame('move', $operations[0]->getType()->value);
        $this->assertSame('Campaigns', $operations[0]->getSourcePrefix());
        $this->assertSame('Archive/Campaigns', $operations[0]->getTargetPrefix());
        $this->assertSame('bytes', $adapter->read('Archive/Campaigns/img.jpg'));
    }

    public function testShallowListingOfTargetAncestorShowsMovedDirectory(): void
    {
        // A -> Archive/A pending; listing the (physically nonexistent) ancestor 'Archive' must
        // surface the pending subtree as a directory entry, not silently omit it.
        $adapter = $this->nonRenamingAdapter();
        $adapter->write('A/img.jpg', 'bytes', new Config());
        $this->addMove('A', 'Archive/A');

        $paths = [];
        foreach ($adapter->listContents('Archive', false) as $item) {
            $paths[] = $item->path();
        }

        $this->assertSame(['Archive/A'], $paths);
    }

    public function testDeepListingOfTargetAncestorContainsTranslatedFiles(): void
    {
        $adapter = $this->nonRenamingAdapter();
        $adapter->write('A/img.jpg', 'bytes', new Config());
        $this->addMove('A', 'Archive/A');

        $paths = [];
        foreach ($adapter->listContents('Archive', true) as $item) {
            $paths[] = $item->path();
        }
        sort($paths);

        $this->assertSame(['Archive/A', 'Archive/A/img.jpg'], $paths);
    }

    public function testAncestorListingDedupesLiteralWins(): void
    {
        $adapter = $this->nonRenamingAdapter();
        $adapter->write('A/img.jpg', 'legacy-bytes', new Config());
        $this->addMove('A', 'Archive/A');
        // a fresh upload already lands literally at the translated path
        $adapter->write('Archive/A/x', 'fresh-bytes', new Config());

        $items = [];
        foreach ($adapter->listContents('Archive', true) as $item) {
            $items[$item->path()] = $item;
        }

        $this->assertArrayHasKey('Archive/A/x', $items);
        $this->assertCount(1, array_filter(array_keys($items), static fn ($p) => $p === 'Archive/A/x'));
        // literal wins: content must be the fresh upload, not the (nonexistent) translated candidate
        $this->assertSame('fresh-bytes', $adapter->read('Archive/A/x'));
    }

    public function testRootListingIncludesPendingTargets(): void
    {
        $adapter = $this->nonRenamingAdapter();
        $adapter->write('A/img.jpg', 'bytes', new Config());
        $this->addMove('A', 'Archive/A');

        $paths = [];
        foreach ($adapter->listContents('', false) as $item) {
            $paths[] = $item->path();
        }

        $this->assertContains('Archive', $paths);
    }

    public function testDeleteRemovesLiteralAndStaleMappedCandidate(): void
    {
        // A -> B pending with legacy content still at A; a fresh literal lands at B and shadows
        // it. Deleting the literal must also remove the stale mapped candidate at A, otherwise
        // the logical path resurrects the legacy object once the literal is gone.
        $adapter = $this->adapter();
        $adapter->write('A/x.jpg', 'legacy', new Config());
        $this->addMove('A', 'B');
        $adapter->write('B/x.jpg', 'new-bytes', new Config());

        $adapter->delete('B/x.jpg');

        $this->assertFalse($adapter->fileExists('B/x.jpg'), 'must not resurrect the stale legacy object');
        $this->assertFalse($this->adapter()->fileExists('A/x.jpg'), 'stale legacy object must be physically gone');
    }

    public function testSingleFileMoveOfShadowingLiteralCleansStaleCandidate(): void
    {
        // Same setup as above, but the logical path is vacated by a single-file move rather than
        // a delete: the stale candidate at A must not resurrect once B/x.jpg is gone.
        $adapter = $this->adapter();
        $adapter->write('A/x.jpg', 'legacy', new Config());
        $this->addMove('A', 'B');
        $adapter->write('B/x.jpg', 'new-bytes', new Config());

        $adapter->move('B/x.jpg', 'Elsewhere/x.jpg', new Config());

        $this->assertSame('new-bytes', $adapter->read('Elsewhere/x.jpg'), 'the literal wins the move');
        $this->assertFalse($adapter->fileExists('B/x.jpg'));
        $this->assertFalse($this->adapter()->fileExists('A/x.jpg'), 'stale legacy object must be physically gone');
    }
}
