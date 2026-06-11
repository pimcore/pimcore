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

    public function testForFullPathThenEncodeComposes(): void
    {
        self::assertSame(
            '/var/assets/Car%20Images/M%C3%B6tley.jpg',
            (new AssetWebPath())->encode((new AssetWebPath())->forFullPath('/Car Images/Mötley.jpg')),
        );
    }
}
