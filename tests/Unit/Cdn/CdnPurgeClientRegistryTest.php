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

use Pimcore\Cdn\CdnPurgeClientRegistry;
use Pimcore\Cdn\PurgeClientInterface;
use Pimcore\Tests\Support\Test\TestCase;
use Psr\Log\LoggerInterface;

class CdnPurgeClientRegistryTest extends TestCase
{
    private function makeClient(string $provider, bool $expectCall = false): PurgeClientInterface
    {
        $mock = $this->createMock(PurgeClientInterface::class);
        if ($expectCall) {
            $mock->expects($this->atLeastOnce())->method('purgeByTag');
        }

        return $mock;
    }

    private function buildRegistry(array $clients, string $provider): CdnPurgeClientRegistry
    {
        $logger = $this->createMock(LoggerInterface::class);

        // Wrap in an ArrayIterator keyed by provider name, matching TaggedIterator indexAttribute behavior
        $iterator = new \ArrayIterator($clients);

        return new CdnPurgeClientRegistry($iterator, $provider, $logger);
    }

    public function testEmptyProviderResolvesToNullClient(): void
    {
        $nullClient = $this->createMock(PurgeClientInterface::class);
        $nullClient->expects($this->once())->method('purgeByTag')->with('some-tag');

        $registry = $this->buildRegistry(['null' => $nullClient], '');
        $registry->purgeByTag('some-tag');
    }

    public function testFastlyProviderResolvesToFastlyClient(): void
    {
        $fastlyClient = $this->createMock(PurgeClientInterface::class);
        $fastlyClient->expects($this->once())->method('purgeByTag')->with('asset-42');

        $nullClient = $this->createMock(PurgeClientInterface::class);
        $nullClient->expects($this->never())->method('purgeByTag');

        $registry = $this->buildRegistry(['null' => $nullClient, 'fastly' => $fastlyClient], 'fastly');
        $registry->purgeByTag('asset-42');
    }

    public function testUnknownProviderLogsWarningAndFallsBackToNullClient(): void
    {
        $nullClient = $this->createMock(PurgeClientInterface::class);
        $nullClient->expects($this->once())->method('purgeByTag');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with(
            $this->stringContains('not registered'),
            $this->arrayHasKey('provider'),
        );

        $registry = new CdnPurgeClientRegistry(
            new \ArrayIterator(['null' => $nullClient]),
            'unknown-provider',
            $logger,
        );

        $registry->purgeByTag('some-tag');
    }

    public function testClientIsResolvedOnlyOnce(): void
    {
        $nullClient = $this->createMock(PurgeClientInterface::class);
        $nullClient->expects($this->exactly(3))->method('purgeByTag');

        $registry = $this->buildRegistry(['null' => $nullClient], '');

        $registry->purgeByTag('tag-1');
        $registry->purgeByTag('tag-2');
        $registry->purgeByTag('tag-3');
    }

    public function testPurgeByTagsDelegatesToResolvedClient(): void
    {
        $fastlyClient = $this->createMock(PurgeClientInterface::class);
        $fastlyClient->expects($this->once())
            ->method('purgeByTags')
            ->with(['asset-1', 'asset-2']);

        $registry = $this->buildRegistry(['fastly' => $fastlyClient, 'null' => $this->createMock(PurgeClientInterface::class)], 'fastly');
        $registry->purgeByTags(['asset-1', 'asset-2']);
    }

    public function testPurgeByUrlDelegatesToResolvedClient(): void
    {
        $fastlyClient = $this->createMock(PurgeClientInterface::class);
        $fastlyClient->expects($this->once())
            ->method('purgeByUrl')
            ->with('https://cdn.example.com/var/assets/image.jpg');

        $registry = $this->buildRegistry(['fastly' => $fastlyClient, 'null' => $this->createMock(PurgeClientInterface::class)], 'fastly');
        $registry->purgeByUrl('https://cdn.example.com/var/assets/image.jpg');
    }
}
