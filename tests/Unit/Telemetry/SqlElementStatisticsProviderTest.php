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

namespace Pimcore\Tests\Unit\Telemetry;

use Doctrine\DBAL\Connection;
use Pimcore\Telemetry\Snapshot\SnapshotQueryRunner;
use Pimcore\Telemetry\Snapshot\Statistics\ElementKind;
use Pimcore\Telemetry\Snapshot\Statistics\SqlElementStatisticsProvider;
use Pimcore\Tests\Support\Test\TestCase;
use function str_contains;

class SqlElementStatisticsProviderTest extends TestCase
{
    public function testTypeCountsAggregatesTheGroupByResult(): void
    {
        $counts = $this->provider()->typeCounts(ElementKind::Asset);

        $this->assertSame(4, $counts->total());
        $this->assertSame(3, $counts->ofType('image'));
        $this->assertSame(0, $counts->ofType('video'));
        $this->assertSame(2, $counts->distinctTypes());
    }

    public function testTreeDepthReadsTheCombinedMaxAvgScan(): void
    {
        $depth = $this->provider()->treeDepth(ElementKind::DataObject);

        $this->assertSame(4, $depth->max);
        $this->assertSame(2, $depth->avg); // round(2.0)
    }

    public function testVariantAndFanoutShape(): void
    {
        $provider = $this->provider();

        $this->assertSame(2, $provider->objectsWithVariants());
        $this->assertSame(3, $provider->maxVariantsPerObject());
        $this->assertSame(50, $provider->maxObjectFanout());
    }

    private function provider(): SqlElementStatisticsProvider
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnArgument(0);
        $connection->method('fetchAllKeyValue')->willReturn(['image' => 3, 'folder' => 1]);
        $connection->method('fetchAssociative')->willReturn(['max_d' => 4, 'avg_d' => 2.0]);
        $connection->method('fetchOne')->willReturnCallback(
            static fn (string $sql): int => match (true) {
                str_contains($sql, 'DISTINCT') => 2,                                  // objectsWithVariants
                str_contains($sql, 'MAX(c)') && str_contains($sql, "'variant'") => 3, // maxVariantsPerObject
                str_contains($sql, 'MAX(c)') => 50,                                   // maxObjectFanout
                default => 0,
            }
        );

        return new SqlElementStatisticsProvider(new SnapshotQueryRunner($connection, 0));
    }
}
