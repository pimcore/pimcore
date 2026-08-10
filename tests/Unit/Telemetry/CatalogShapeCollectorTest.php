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

use Pimcore\Telemetry\Snapshot\Bucketizer;
use Pimcore\Telemetry\Snapshot\CatalogShapeCollector;
use Pimcore\Telemetry\Snapshot\Statistics\ElementKind;
use Pimcore\Telemetry\Snapshot\Statistics\ElementStatisticsProviderInterface;
use Pimcore\Telemetry\Snapshot\Statistics\TreeDepth;
use Pimcore\Tests\Support\Test\TestCase;

class CatalogShapeCollectorTest extends TestCase
{
    public function testNamespaceIsCatalog(): void
    {
        $this->assertSame('catalog', $this->collector()->getNamespace());
    }

    public function testEmitsShapeMetricsFromTheStatisticsProviderAndIsContentNever(): void
    {
        $metrics = $this->collector()->collect();

        $this->assertSame(1, $metrics['schema_version']);
        $this->assertSame(9, $metrics['object_tree_max_depth']);
        $this->assertSame(5, $metrics['object_tree_avg_depth']);
        $this->assertSame(4, $metrics['asset_tree_max_depth']);
        $this->assertSame(5, $metrics['document_tree_max_depth']);
        $this->assertSame('11-50', $metrics['products_with_variants_bucket']); // bucket(12)
        $this->assertSame(7, $metrics['max_variants_per_product']);
        $this->assertSame(43, $metrics['max_folder_fanout']);

        foreach ($metrics as $key => $value) {
            $this->assertIsScalar($value, "metric '$key' must be scalar");
        }
    }

    private function collector(): CatalogShapeCollector
    {
        $statistics = $this->createMock(ElementStatisticsProviderInterface::class);
        $statistics->method('treeDepth')->willReturnCallback(
            static fn (ElementKind $kind): TreeDepth => match ($kind) {
                ElementKind::DataObject => new TreeDepth(9, 5),
                ElementKind::Asset => new TreeDepth(4, 3),
                ElementKind::Document => new TreeDepth(5, 3),
            }
        );
        $statistics->method('objectsWithVariants')->willReturn(12);
        $statistics->method('maxVariantsPerObject')->willReturn(7);
        $statistics->method('maxObjectFanout')->willReturn(43);

        return new CatalogShapeCollector($statistics, new Bucketizer());
    }
}
