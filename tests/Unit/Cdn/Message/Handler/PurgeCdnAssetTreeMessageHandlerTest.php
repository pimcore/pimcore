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

namespace Pimcore\Tests\Unit\Cdn\Message\Handler;

use Pimcore\Cdn\AssetWebPath;
use Pimcore\Cdn\CdnAssetTag;
use Pimcore\Cdn\Message\Handler\PurgeCdnAssetTreeMessageHandler;
use Pimcore\Cdn\Message\PurgeCdnAssetTreeMessage;
use Pimcore\Cdn\PurgeClientInterface;
use Pimcore\Tests\Support\Test\TestCase;

class PurgeCdnAssetTreeMessageHandlerTest extends TestCase
{
    /** @var object{tagBatches: array<int, string[]>, urls: string[]} */
    private object $captured;

    private PurgeClientInterface $client;

    protected function setUp(): void
    {
        $this->captured = new class() {
            /** @var array<int, string[]> */
            public array $tagBatches = [];

            /** @var string[] */
            public array $urls = [];
        };

        $captured = $this->captured;
        $client = $this->createMock(PurgeClientInterface::class);
        $client->method('purgeByTags')->willReturnCallback(function (array $tags) use ($captured): void {
            $captured->tagBatches[] = $tags;
        });
        $client->method('purgeByUrl')->willReturnCallback(function (string $url) use ($captured): void {
            $captured->urls[] = $url;
        });
        $this->client = $client;
    }

    /**
     * Builds a handler whose DB descendant lookup is stubbed with a fixed list.
     *
     * @param array<int, array{id: int, fullPath: string}> $descendants
     */
    private function handler(array $descendants, string $cdnBaseUrl = ''): PurgeCdnAssetTreeMessageHandler
    {
        return new class($this->client, new CdnAssetTag(), new AssetWebPath(), $cdnBaseUrl, $descendants) extends PurgeCdnAssetTreeMessageHandler {
            /**
             * @param array<int, array{id: int, fullPath: string}> $descendants
             */
            public function __construct(
                PurgeClientInterface $purgeClient,
                CdnAssetTag $assetTag,
                AssetWebPath $assetWebPath,
                string $cdnBaseUrl,
                private readonly array $descendants,
            ) {
                parent::__construct($purgeClient, $assetTag, $assetWebPath, $cdnBaseUrl);
            }

            protected function loadDescendants(string $folderPath): iterable
            {
                return $this->descendants;
            }
        };
    }

    private function pathHashTag(string $fullPath): string
    {
        return 'asset-path-' . substr(hash('sha256', '/var/assets' . $fullPath), 0, 12);
    }

    public function testPurgesIdAndOldPathTagsForEachDescendant(): void
    {
        $handler = $this->handler([
            ['id' => 10, 'fullPath' => '/catalog/a.jpg'],
            ['id' => 11, 'fullPath' => '/catalog/sub/b.png'],
        ]);

        $handler(new PurgeCdnAssetTreeMessage('/products', '/catalog'));

        // Descendants live under the NEW path in the DB; the purge must target their
        // OLD paths (the CDN cached them under those) plus their asset-{id} thumbnail tags.
        $this->assertCount(1, $this->captured->tagBatches);
        $this->assertEqualsCanonicalizing(
            [
                'asset-10',
                $this->pathHashTag('/products/a.jpg'),
                'asset-11',
                $this->pathHashTag('/products/sub/b.png'),
            ],
            $this->captured->tagBatches[0],
        );
    }

    public function testDispatchesUrlPurgesForOldEncodedPathsWhenBaseUrlConfigured(): void
    {
        $handler = $this->handler(
            [['id' => 7, 'fullPath' => '/catalog/Car Images/Mötley.jpg']],
            'https://cdn.example.com',
        );

        $handler(new PurgeCdnAssetTreeMessage('/products', '/catalog'));

        $this->assertSame(
            ['https://cdn.example.com/var/assets/products/Car%20Images/M%C3%B6tley.jpg'],
            $this->captured->urls,
        );
    }

    public function testNoUrlPurgesWhenBaseUrlEmpty(): void
    {
        $handler = $this->handler([['id' => 7, 'fullPath' => '/catalog/a.jpg']]);

        $handler(new PurgeCdnAssetTreeMessage('/products', '/catalog'));

        $this->assertSame([], $this->captured->urls);
        $this->assertCount(1, $this->captured->tagBatches);
    }

    public function testNoDescendantsMakesNoPurgeCalls(): void
    {
        $handler = $this->handler([]);

        $handler(new PurgeCdnAssetTreeMessage('/products', '/catalog'));

        $this->assertSame([], $this->captured->tagBatches);
        $this->assertSame([], $this->captured->urls);
    }

    public function testDeepRenameKeepsRelativeSubPathsIntact(): void
    {
        // Moving /products into /archive/products: only the folder prefix changes,
        // the descendant's path relative to the folder must survive untouched.
        $handler = $this->handler([
            ['id' => 3, 'fullPath' => '/archive/products/2024/summer/img.jpg'],
        ]);

        $handler(new PurgeCdnAssetTreeMessage('/products', '/archive/products'));

        $this->assertEqualsCanonicalizing(
            ['asset-3', $this->pathHashTag('/products/2024/summer/img.jpg')],
            $this->captured->tagBatches[0],
        );
    }
}
