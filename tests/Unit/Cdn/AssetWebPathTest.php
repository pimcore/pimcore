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

use Pimcore\Cdn\AssetWebPath;
use Pimcore\Tests\Support\Test\TestCase;

class AssetWebPathTest extends TestCase
{
    public function testForFullPathPrependsPrefix(): void
    {
        self::assertSame('/var/assets/folder/image.jpg', (new AssetWebPath())->forFullPath('/folder/image.jpg'));
    }

    public function testEncodeLeavesSafePathUnchanged(): void
    {
        self::assertSame('/var/assets/folder/image.jpg', (new AssetWebPath())->encode('/var/assets/folder/image.jpg'));
    }

    public function testEncodePercentEncodesEachSegmentButPreservesSlashes(): void
    {
        self::assertSame(
            '/var/assets/Car%20Images/M%C3%B6tley.jpg',
            (new AssetWebPath())->encode('/var/assets/Car Images/Mötley.jpg'),
        );
    }

    public function testForFullPathUsesConfiguredPrefix(): void
    {
        // The prefix follows assets.frontend_prefixes.source (via the
        // pimcore.cdn.original_asset_prefix parameter) so purge URLs/tags always
        // match the URLs Asset::getFrontendFullPath() actually emits.
        self::assertSame('/media/folder/image.jpg', (new AssetWebPath('/media'))->forFullPath('/folder/image.jpg'));
    }

    public function testIsOriginalAssetPathMatchesConfiguredPrefixOnly(): void
    {
        $custom = new AssetWebPath('/media');

        self::assertTrue($custom->isOriginalAssetPath('/media/folder/image.jpg'));
        self::assertFalse($custom->isOriginalAssetPath('/var/assets/folder/image.jpg'));
    }

    public function testIsOriginalAssetPathDefaultsToVarAssets(): void
    {
        $default = new AssetWebPath();

        self::assertTrue($default->isOriginalAssetPath('/var/assets/folder/image.jpg'));
        self::assertFalse($default->isOriginalAssetPath('/some/page'));
    }

    public function testIsOriginalAssetPathRequiresFullPrefixSegment(): void
    {
        // The prefix must match as a complete path segment — a sibling path that merely
        // shares the prefix characters must not be treated as an original asset.
        $default = new AssetWebPath();

        self::assertFalse($default->isOriginalAssetPath('/var/assets-archive/x.jpg'));
        self::assertFalse($default->isOriginalAssetPath('/var/assets'));
    }

    public function testEncodeKeepsRetinaSuffixLiteral(): void
    {
        // encode() delegates to urlencode_ignore_slash(), the encoder behind the URLs
        // Pimcore actually emits (Asset::getFrontendFullPath) — including its exemption
        // that keeps `@2x.` retina suffixes literal instead of `%402x.`. Purge/IO URLs
        // must match the emitted form, not a plain rawurlencode of it.
        self::assertSame(
            '/var/assets/products/photo@2x.jpg',
            (new AssetWebPath())->encode('/var/assets/products/photo@2x.jpg'),
        );
    }

    public function testForFullPathThenEncodeComposes(): void
    {
        self::assertSame(
            '/var/assets/Car%20Images/M%C3%B6tley.jpg',
            (new AssetWebPath())->encode((new AssetWebPath())->forFullPath('/Car Images/Mötley.jpg')),
        );
    }
}
