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
use Pimcore\Telemetry\Snapshot\CatalogShapeCollector;
use Pimcore\Telemetry\Snapshot\SnapshotQueryRunner;
use Pimcore\Telemetry\Snapshot\Statistics\ElementKind;
use Pimcore\Telemetry\Snapshot\Statistics\ElementStatisticsProviderInterface;
use Pimcore\Telemetry\Snapshot\Statistics\TreeDepth;
use Pimcore\Tests\Support\Test\TestCase;
use RuntimeException;
use function preg_match;
use function preg_quote;
use function str_contains;

class CatalogShapeCollectorTest extends TestCase
{
    /**
     * @var list<string>
     */
    private array $executedSql = [];

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
        $this->assertSame(12, $metrics['products_with_variants']);
        $this->assertSame(7, $metrics['max_variants_per_product']);
        $this->assertSame(43, $metrics['max_folder_fanout']);

        foreach ($metrics as $key => $value) {
            $this->assertIsScalar($value, "metric '$key' must be scalar");
        }
    }

    /**
     * Assortment breadth is a raw count now - the bucket hid exactly the differences that make one
     * catalog interesting versus another.
     */
    public function testAssortmentBreadthIsARawInteger(): void
    {
        $metrics = $this->collector()->collect();

        $this->assertIsInt($metrics['products_with_variants']);
        $this->assertArrayNotHasKey('products_with_variants_bucket', $metrics);
    }

    /**
     * Volume says how much content exists; these say how hard it is being worked. 2,624 metadata rows
     * over 368 assets is a real DAM discipline, not a file dump - a distinction asset_count cannot make.
     */
    public function testReportsContentRichness(): void
    {
        $metrics = $this->collector()->collect();

        $this->assertSame(2_624, $metrics['asset_metadata_count']);
        $this->assertSame(1_329, $metrics['document_editable_count']);
        $this->assertSame(593, $metrics['property_count']);
        $this->assertSame(23, $metrics['tag_count']);
        $this->assertSame(506, $metrics['tag_assignment_count']);
        $this->assertSame(229, $metrics['note_count']);
        $this->assertSame(4, $metrics['object_url_slug_count']);
    }

    public function testAFailedRichnessCountIsOmittedRatherThanZeroed(): void
    {
        $metrics = $this->collector(failFor: 'tags_assignment')->collect();

        $this->assertArrayNotHasKey('tag_assignment_count', $metrics);
        $this->assertArrayHasKey('tag_count', $metrics, 'unrelated counts must survive');
    }

    private function collector(?string $failFor = null): CatalogShapeCollector
    {
        $this->executedSql = [];

        $counts = [
            'assets_metadata'    => 2_624,
            'documents_editables' => 1_329,
            'properties'         => 593,
            'tags_assignment'    => 506,
            'notes'              => 229,
            'tags'               => 23,
            'object_url_slugs'   => 4,
        ];

        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnArgument(0);
        $connection->method('fetchOne')->willReturnCallback(
            function (string $sql) use ($counts, $failFor): int {
                $this->executedSql[] = $sql;

                if ($failFor !== null && str_contains($sql, $failFor)) {
                    throw new RuntimeException('max_statement_time exceeded');
                }

                foreach ($counts as $table => $value) {
                    if (preg_match('/\bFROM ' . preg_quote($table, '/') . '\b/', $sql) === 1) {
                        return $value;
                    }
                }

                return 0;
            }
        );

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

        return new CatalogShapeCollector($statistics, new SnapshotQueryRunner($connection, 0));
    }
}
