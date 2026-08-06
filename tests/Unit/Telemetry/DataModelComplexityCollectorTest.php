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
use Pimcore\Telemetry\Snapshot\Bucketizer;
use Pimcore\Telemetry\Snapshot\DataModelComplexityCollector;
use Pimcore\Telemetry\Snapshot\FieldTreeAnalyzer;
use Pimcore\Telemetry\Snapshot\SnapshotQueryRunner;
use Pimcore\Tests\Support\Test\TestCase;
use function is_array;

class DataModelComplexityCollectorTest extends TestCase
{
    public function testNamespaceIsDatamodel(): void
    {
        $this->assertSame('datamodel', $this->collector()->getNamespace());
    }

    public function testEmitsExpectedKeysAndIsContentNever(): void
    {
        $metrics = $this->collector()->collect();

        $expectedKeys = [
            'schema_version', 'class_count', 'fieldcollection_count', 'objectbrick_count',
            'custom_layout_count', 'classificationstore_group_count', 'classificationstore_key_count',
            'total_field_count', 'max_fields_per_class', 'avg_fields_per_class', 'max_nesting_depth',
            'classes_with_inheritance', 'distinct_fieldtype_count', 'relation_field_count',
            'fieldtype_usage', 'uses_localizedfields', 'uses_blocks', 'uses_classificationstore',
            'uses_calculated_value', 'uses_advanced_relations',
        ];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $metrics, "missing metric '$key'");
        }

        // Locks the emitted key-set so a future leaked/extra key - even a scalar one - is caught.
        $this->assertCount(20, $metrics);

        $this->assertSame(1, $metrics['schema_version']);

        // Content-never: scalars, or (fieldtype_usage) a map whose leaves are all scalar.
        foreach ($metrics as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $leaf) {
                    $this->assertIsScalar($leaf, "map metric '$key' must contain only scalars");
                }

                continue;
            }
            $this->assertIsScalar($value, "metric '$key' must be scalar");
        }
    }

    private function collector(): DataModelComplexityCollector
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnArgument(0);
        $connection->method('fetchOne')->willReturn(0);

        return new DataModelComplexityCollector(
            new FieldTreeAnalyzer(),
            new Bucketizer(),
            new SnapshotQueryRunner($connection, 0),
        );
    }
}
