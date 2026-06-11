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

namespace Pimcore\Tests\Unit\Cdn;

use Pimcore\Cdn\CdnAssetTag;
use Pimcore\Tests\Support\Test\TestCase;

class CdnAssetTagTest extends TestCase
{
    public function testForAsset(): void
    {
        self::assertSame('asset-123', (new CdnAssetTag())->forAsset(123));
    }

    public function testForThumbConfig(): void
    {
        self::assertSame('thumb-myThumb', (new CdnAssetTag())->forThumbConfig('myThumb'));
    }

    public function testForAssetThumb(): void
    {
        self::assertSame('asset-123-thumb-myThumb', (new CdnAssetTag())->forAssetThumb(123, 'myThumb'));
    }

    public function testForPathPinsHashAlgorithm(): void
    {
        // Pinned value: the tagging side (CdnSurrogateKeyListener) and every purge side
        // (CdnPurgeListener, CdnPurgeCommand) MUST produce this exact string for the same web
        // path, or a purge silently misses. Changing the hash here is a breaking change.
        self::assertSame(
            'asset-path-c17ea8c4a328',
            (new CdnAssetTag())->forPath('/var/assets/folder/image.jpg'),
        );
    }

    public function testForPathFormat(): void
    {
        self::assertMatchesRegularExpression('/^asset-path-[0-9a-f]{12}$/', (new CdnAssetTag())->forPath('/var/assets/x.jpg'));
    }
}
