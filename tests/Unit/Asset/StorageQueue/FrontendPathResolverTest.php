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
use DateTimeImmutable;
use Pimcore\Asset\StorageQueue\FrontendPathResolver;
use Pimcore\Asset\StorageQueue\StorageOperation;
use Pimcore\Asset\StorageQueue\StorageOperationType;

class FrontendPathResolverTest extends Unit
{
    private InMemoryStorageOperationQueueRepository $repository;

    protected function _before(): void
    {
        $this->repository = new InMemoryStorageOperationQueueRepository();
    }

    private function addMove(string $source, string $target): void
    {
        $this->repository->add(new StorageOperation(
            null, 'asset', StorageOperationType::Move, $source, $target, new DateTimeImmutable()
        ));
    }

    public function testDisabledResolverIsIdentity(): void
    {
        $this->addMove('Campaigns', 'Archive/Campaigns');
        $resolver = new FrontendPathResolver($this->repository, false);

        $this->assertSame('/Archive/Campaigns/a.jpg', $resolver->resolvePhysicalPath('/Archive/Campaigns/a.jpg'));
    }

    public function testEmptyQueueIsIdentity(): void
    {
        $resolver = new FrontendPathResolver($this->repository, true);

        $this->assertSame('/Campaigns/a.jpg', $resolver->resolvePhysicalPath('/Campaigns/a.jpg'));
    }

    public function testCoveredPathMapsToSourcePrefix(): void
    {
        $this->addMove('Campaigns', 'Archive/Campaigns');
        $resolver = new FrontendPathResolver($this->repository, true);

        $this->assertSame('/Campaigns/sub/a.jpg', $resolver->resolvePhysicalPath('/Archive/Campaigns/sub/a.jpg'));
    }

    public function testExactPrefixMatchMaps(): void
    {
        $this->addMove('Campaigns', 'Archive/Campaigns');
        $resolver = new FrontendPathResolver($this->repository, true);

        $this->assertSame('/Campaigns', $resolver->resolvePhysicalPath('/Archive/Campaigns'));
    }

    public function testUncoveredPathIsIdentity(): void
    {
        $this->addMove('Campaigns', 'Archive/Campaigns');
        $resolver = new FrontendPathResolver($this->repository, true);

        $this->assertSame('/Other/a.jpg', $resolver->resolvePhysicalPath('/Other/a.jpg'));
    }

    public function testSiblingPrefixIsNotCovered(): void
    {
        // 'Archive/CampaignsOld' must not be treated as covered by target 'Archive/Campaigns'
        $this->addMove('Campaigns', 'Archive/Campaigns');
        $resolver = new FrontendPathResolver($this->repository, true);

        $this->assertSame('/Archive/CampaignsOld/a.jpg', $resolver->resolvePhysicalPath('/Archive/CampaignsOld/a.jpg'));
    }

    public function testMostSpecificCoveringRowWins(): void
    {
        $this->addMove('Old/Inner', 'Top/Mid/Inner');
        $this->addMove('Old/Mid', 'Top/Mid');
        $resolver = new FrontendPathResolver($this->repository, true);

        $this->assertSame('/Old/Inner/a.jpg', $resolver->resolvePhysicalPath('/Top/Mid/Inner/a.jpg'));
    }

    public function testMultibyteSuffixIsPreserved(): void
    {
        $this->addMove('Möbel', 'Archiv/Möbel');
        $resolver = new FrontendPathResolver($this->repository, true);

        $this->assertSame('/Möbel/Öfen/ü.jpg', $resolver->resolvePhysicalPath('/Archiv/Möbel/Öfen/ü.jpg'));
    }

    public function testRootPathIsIdentity(): void
    {
        $this->addMove('Campaigns', 'Archive/Campaigns');
        $resolver = new FrontendPathResolver($this->repository, true);

        $this->assertSame('/', $resolver->resolvePhysicalPath('/'));
    }
}
