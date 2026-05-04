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

use Pimcore\Cdn\NullPurgeClient;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\LoggerInterface;

class NullPurgeClientTest extends TestCase
{
    public function testPurgeByTagLogsAtDebugLevel(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with(
                $this->stringContains('disabled'),
                $this->callback(fn (array $ctx) => ($ctx['tag'] ?? null) === 'asset-42'),
            );

        (new NullPurgeClient($logger))->purgeByTag('asset-42');
    }

    public function testPurgeByTagsLogsTagsAtDebugLevel(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with(
                $this->stringContains('disabled'),
                $this->callback(fn (array $ctx) => str_contains($ctx['tags'] ?? '', 'asset-1')),
            );

        (new NullPurgeClient($logger))->purgeByTags(['asset-1', 'thumb-hero']);
    }

    public function testPurgeByUrlLogsUrlAtDebugLevel(): void
    {
        $url = 'https://cdn.example.com/var/assets/image.jpg';

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with(
                $this->stringContains('disabled'),
                $this->callback(fn (array $ctx) => ($ctx['url'] ?? null) === $url),
            );

        (new NullPurgeClient($logger))->purgeByUrl($url);
    }
}
