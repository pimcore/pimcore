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

use Pimcore\Cdn\Message\Handler\PurgeCdnMessageHandler;
use Pimcore\Cdn\Message\PurgeCdnTagMessage;
use Pimcore\Cdn\Message\PurgeCdnUrlMessage;
use Pimcore\Cdn\PurgeClientInterface;
use Pimcore\Tests\Support\Test\TestCase;

class PurgeCdnMessageHandlerTest extends TestCase
{
    public function testTagMessageDispatchesPurgeByTag(): void
    {
        $purgeClient = $this->createMock(PurgeClientInterface::class);
        $purgeClient->expects($this->once())->method('purgeByTag')->with('asset-42');
        $purgeClient->expects($this->never())->method('purgeByUrl');

        $handler = new PurgeCdnMessageHandler($purgeClient);
        $handler(new PurgeCdnTagMessage('asset-42'));
    }

    public function testUrlMessageDispatchesPurgeByUrl(): void
    {
        $url = 'https://cdn.example.com/var/assets/image.jpg';

        $purgeClient = $this->createMock(PurgeClientInterface::class);
        $purgeClient->expects($this->once())->method('purgeByUrl')->with($url);
        $purgeClient->expects($this->never())->method('purgeByTag');

        $handler = new PurgeCdnMessageHandler($purgeClient);
        $handler(new PurgeCdnUrlMessage($url));
    }
}
