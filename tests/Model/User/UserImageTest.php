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

namespace Pimcore\Tests\Model\User;

use League\Flysystem\FilesystemOperator;
use Pimcore;
use Pimcore\Model\User;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tool\Storage;
use Psr\Container\ContainerInterface;
use ReflectionProperty;

class UserImageTest extends ModelTestCase
{
    /**
     * Regression test: some storage adapters close the passed stream inside writeStream(), which
     * must not make setImage() fail on its own fclose() (pimcore/platform-version#355).
     */
    public function testSetImageToleratesAdapterClosingTheStream(): void
    {
        $writtenPaths = [];

        $storage = $this->createMock(FilesystemOperator::class);
        $storage->method('fileExists')->willReturn(false);
        $storage->method('writeStream')->willReturnCallback(
            function (string $path, $contents) use (&$writtenPaths): void {
                $writtenPaths[] = $path;
                fclose($contents);
            }
        );

        $imagePath = tempnam(sys_get_temp_dir(), 'user-image');
        file_put_contents($imagePath, 'image');

        $user = new User();
        $user->setId(355);

        try {
            $this->withAdminStorage($storage, static fn () => $user->setImage($imagePath));
        } finally {
            unlink($imagePath);
        }

        $this->assertSame(['/user-image/user-355.png'], $writtenPaths);
    }

    /**
     * Runs $callback with the admin storage replaced by $storage, see
     * AssetThumbnailCacheTest::withThumbnailStorage() for why the locator is swapped.
     */
    private function withAdminStorage(FilesystemOperator $storage, callable $callback): mixed
    {
        $storageService = Pimcore::getContainer()->get(Storage::class);

        $property = new ReflectionProperty(Storage::class, 'locator');
        $originalLocator = $property->getValue($storageService);

        $property->setValue($storageService, new class($storage, $originalLocator) implements ContainerInterface {
            public function __construct(
                private FilesystemOperator $adminStorage,
                private ContainerInterface $original,
            ) {
            }

            public function has(string $id): bool
            {
                return $id === 'pimcore.admin.storage' || $this->original->has($id);
            }

            public function get(string $id): mixed
            {
                return $id === 'pimcore.admin.storage'
                    ? $this->adminStorage
                    : $this->original->get($id);
            }
        });

        try {
            return $callback();
        } finally {
            $property->setValue($storageService, $originalLocator);
        }
    }
}
