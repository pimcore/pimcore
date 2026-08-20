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
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\Visibility;
use Pimcore\Asset\StorageQueue\QueueAwareStorageAdapter;

/**
 * League's official adapter conformance suite (league/flysystem-adapter-test-utilities ^3.0)
 * cannot run under this repository's PHPUnit version: its ~50 test methods are marked with
 * `@test` docblock annotations only, a convention PHPUnit dropped in 10.0 in favour of the
 * `#[Test]` attribute (see PHPUnit's UpgradeGuide - "@test annotation ... removed"). Pimcore core
 * requires phpunit/phpunit ^12.5 || ^13.1, so PHPUnit\Util\Test::isTestMethod() (which
 * Codeception's Unit loader relies on) recognises none of the suite's methods and Codeception
 * reports "No tests executed!" for the whole class - confirmed against the installed
 * league/flysystem-adapter-test-utilities 3.30.1, the latest tagged 3.x release (patching the
 * vendor package, or depending on its unreleased 4.x-dev branch, is out of scope for a stable
 * dev dependency).
 *
 * This is the documented fallback: a scripted sequence exercising all 17 FilesystemAdapter
 * operations against (a) a bare InMemoryFilesystemAdapter and (b) that same kind of adapter
 * wrapped by QueueAwareStorageAdapter with an empty queue, asserting identical outcomes at every
 * step. ChecksumProvider/PublicUrlGenerator/TemporaryUrlGenerator coverage already exists in
 * QueueAwareStorageAdapterTest.
 */
class QueueAwareStorageAdapterParityTest extends Unit
{
    private FilesystemAdapter $bare;

    private QueueAwareStorageAdapter $wrapped;

    protected function _before(): void
    {
        $this->bare = new InMemoryFilesystemAdapter();
        $this->wrapped = new QueueAwareStorageAdapter(
            new InMemoryFilesystemAdapter(),
            new InMemoryStorageOperationQueueRepository(),
            'asset',
        );
    }

    public function testAllSeventeenFilesystemAdapterOperationsMatchWithAnEmptyQueue(): void
    {
        // 1. write
        $this->bothCall(fn (FilesystemAdapter $a) => $a->write('folder/file.txt', 'content', new Config()));

        // 2. fileExists
        $this->assertParity(fn (FilesystemAdapter $a) => $a->fileExists('folder/file.txt'));
        $this->assertParity(fn (FilesystemAdapter $a) => $a->fileExists('folder/missing.txt'));

        // 3. directoryExists
        $this->assertParity(fn (FilesystemAdapter $a) => $a->directoryExists('folder'));
        $this->assertParity(fn (FilesystemAdapter $a) => $a->directoryExists('missing'));

        // 4. read
        $this->assertParity(fn (FilesystemAdapter $a) => $a->read('folder/file.txt'));

        // 5. writeStream
        $this->bothCall(function (FilesystemAdapter $a): void {
            $stream = fopen('php://temp', 'r+b');
            fwrite($stream, 'streamed');
            rewind($stream);
            $a->writeStream('folder/stream.txt', $stream, new Config());
            fclose($stream);
        });

        // 6. readStream
        $this->assertParity(fn (FilesystemAdapter $a) => stream_get_contents($a->readStream('folder/stream.txt')));

        // 7. fileSize
        $this->assertParity(fn (FilesystemAdapter $a) => $a->fileSize('folder/file.txt')->fileSize());

        // 8. mimeType
        $this->assertParity(fn (FilesystemAdapter $a) => $a->mimeType('folder/file.txt')->mimeType());

        // 9. visibility (default)
        $this->assertParity(fn (FilesystemAdapter $a) => $a->visibility('folder/file.txt')->visibility());

        // 10. setVisibility
        $this->bothCall(fn (FilesystemAdapter $a) => $a->setVisibility('folder/file.txt', Visibility::PRIVATE));
        $this->assertParity(fn (FilesystemAdapter $a) => $a->visibility('folder/file.txt')->visibility());

        // 11. lastModified (presence, not the exact timestamp - both use wall-clock time())
        $this->assertParity(fn (FilesystemAdapter $a) => $a->lastModified('folder/file.txt')->lastModified() !== null);

        // 12. createDirectory
        $this->bothCall(fn (FilesystemAdapter $a) => $a->createDirectory('newdir', new Config()));
        $this->assertParity(fn (FilesystemAdapter $a) => $a->directoryExists('newdir'));

        // 13. copy
        $this->bothCall(fn (FilesystemAdapter $a) => $a->copy('folder/file.txt', 'folder/copy.txt', new Config()));
        $this->assertParity(fn (FilesystemAdapter $a) => $a->fileExists('folder/copy.txt'));
        $this->assertParity(fn (FilesystemAdapter $a) => $a->read('folder/copy.txt'));

        // 14. move
        $this->bothCall(fn (FilesystemAdapter $a) => $a->move('folder/copy.txt', 'folder/moved.txt', new Config()));
        $this->assertParity(fn (FilesystemAdapter $a) => $a->fileExists('folder/copy.txt'));
        $this->assertParity(fn (FilesystemAdapter $a) => $a->fileExists('folder/moved.txt'));
        $this->assertParity(fn (FilesystemAdapter $a) => $a->read('folder/moved.txt'));

        // 15. listContents
        $this->assertParity(function (FilesystemAdapter $a): array {
            $paths = [];
            foreach ($a->listContents('', true) as $item) {
                $paths[] = $item->path();
            }
            sort($paths);

            return $paths;
        });

        // 16. delete
        $this->bothCall(fn (FilesystemAdapter $a) => $a->delete('folder/moved.txt'));
        $this->assertParity(fn (FilesystemAdapter $a) => $a->fileExists('folder/moved.txt'));

        // 17. deleteDirectory - a non-existent directory, i.e. Flysystem's no-op contract. NB:
        // deleting a directory that DOES have content is intentionally NOT parity-tested here:
        // QueueAwareStorageAdapter always defers that case to the queue instead of performing it
        // physically (see QueueAwareStorageAdapter::deleteDirectory's "Always deferred" branch),
        // regardless of whether the queue was empty beforehand - that divergence is deliberate
        // and already covered by QueueAwareStorageAdapterTest::testDeleteDirectoryQueuesATombstone.
        $this->bothCall(fn (FilesystemAdapter $a) => $a->deleteDirectory('does-not-exist'));
        $this->assertParity(fn (FilesystemAdapter $a) => $a->directoryExists('does-not-exist'));
    }

    private function bothCall(callable $operation): void
    {
        $operation($this->bare);
        $operation($this->wrapped);
    }

    private function assertParity(callable $probe): void
    {
        $this->assertSame($probe($this->bare), $probe($this->wrapped));
    }
}
