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

use Pimcore\Cdn\NullImageTransformAdapter;
use Pimcore\Tests\Support\Test\TestCase;

class NullImageTransformAdapterTest extends TestCase
{
    public function testReturnsOriginalPathUnchanged(): void
    {
        $adapter = new NullImageTransformAdapter();

        self::assertSame(
            '/var/assets/folder/image.jpg',
            $adapter->buildUrl('/var/assets/folder/image.jpg', ['width' => 400]),
        );
    }
}
