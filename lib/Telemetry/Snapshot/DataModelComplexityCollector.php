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

namespace Pimcore\Telemetry\Snapshot;

use Exception;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Objectbrick;
use function count;
use function is_numeric;
use function max;
use function round;

/**
 * Evidence for "how complex is each customer's data model?" (EM question #2).
 *
 * Emits instance-level structural aggregates over the DataObject data model (classes, field
 * collections, object bricks, classification store, custom layouts). The complexity *score* and
 * tiering are left to the analysis layer (HogQL over these group properties) so thresholds can be
 * tuned without shipping a Pimcore release - the same approach as {@see PillarUsageCollector}.
 *
 * Everything here is content-never: counts, booleans, and Pimcore's own field-type
 * identifiers only - never class, field, product, or any customer names/values. Reads cached
 * definition files (not data tables) so it is cheap and runs only on the periodic snapshot. Any
 * failure degrades to zero, never an exception.
 *
 * Increment {@see self::SCHEMA_VERSION} whenever the emitted evidence set changes.
 *
 * @internal
 */
final readonly class DataModelComplexityCollector implements SnapshotCollectorInterface
{
    private const SCHEMA_VERSION = 1;

    public function __construct(
        private FieldTreeAnalyzer $analyzer,
        private SnapshotQueryRunner $queryRunner,
    ) {
    }

    public function getNamespace(): string
    {
        return 'datamodel';
    }

    public function collect(): array
    {
        $classes = $this->analyzeClasses();
        /** @var FieldTreeMetrics $aggregate */
        $aggregate = $classes['aggregate'];

        // Fold field collections and object bricks into the model-wide totals (not per-class stats).
        foreach ($this->satelliteFieldSets() as $fieldSet) {
            $aggregate = $aggregate->combine($this->analyzer->analyze($fieldSet));
        }

        $classCount = $classes['count'];
        $avgFields = $classCount > 0 ? (int)round($classes['sumFields'] / $classCount) : 0;

        return [
            'schema_version' => self::SCHEMA_VERSION,

            // Breadth.
            'class_count' => $classCount,
            'fieldcollection_count' => $this->fieldcollectionCount(),
            'objectbrick_count' => $this->objectbrickCount(),
            'custom_layout_count' => $this->customLayoutCount(),
            'classificationstore_group_count' => $this->tableCount('classificationstore_groups'),
            'classificationstore_key_count' => $this->tableCount('classificationstore_keys'),

            // Depth.
            'total_field_count' => $aggregate->fieldCount,
            'max_fields_per_class' => $classes['maxFields'],
            'avg_fields_per_class' => $avgFields,
            'max_nesting_depth' => $aggregate->maxDepth,
            'classes_with_inheritance' => $classes['withInheritance'],

            // Richness.
            'distinct_fieldtype_count' => $aggregate->distinctTypeCount(),
            'relation_field_count' => $aggregate->relationFieldCount,
            'fieldtype_usage' => $aggregate->typeUsage,
            'uses_localizedfields' => $aggregate->usesLocalizedfields,
            'uses_blocks' => $aggregate->usesBlocks,
            'uses_classificationstore' => $aggregate->usesClassificationstore,
            'uses_calculated_value' => $aggregate->usesCalculatedValue,
            'uses_advanced_relations' => $aggregate->usesAdvancedRelations,
        ];
    }

    /**
     * @return array{aggregate: FieldTreeMetrics, count: int, maxFields: int, sumFields: int, withInheritance: int}
     */
    private function analyzeClasses(): array
    {
        $aggregate = new FieldTreeMetrics();
        $count = 0;
        $maxFields = 0;
        $sumFields = 0;
        $withInheritance = 0;

        try {
            $classes = (new ClassDefinition\Listing())->getClasses();
        } catch (Exception) {
            $classes = [];
        }

        foreach ($classes as $class) {
            try {
                $fields = $class->getFieldDefinitions(['suppressEnrichment' => true]);
            } catch (Exception) {
                continue;
            }

            $metrics = $this->analyzer->analyze($fields);
            $aggregate = $aggregate->combine($metrics);
            $count++;
            $sumFields += $metrics->fieldCount;
            $maxFields = max($maxFields, $metrics->fieldCount);

            if ($class->getAllowInherit()) {
                $withInheritance++;
            }
        }

        return [
            'aggregate' => $aggregate,
            'count' => $count,
            'maxFields' => $maxFields,
            'sumFields' => $sumFields,
            'withInheritance' => $withInheritance,
        ];
    }

    /**
     * @return list<array<int|string, mixed>>
     */
    private function satelliteFieldSets(): array
    {
        $sets = [];

        try {
            $fieldCollections = (new Fieldcollection\Definition\Listing())->load();
        } catch (Exception) {
            $fieldCollections = [];
        }

        foreach ($fieldCollections as $definition) {
            try {
                $sets[] = $definition->getFieldDefinitions(['suppressEnrichment' => true]);
            } catch (Exception) {
                continue;
            }
        }

        try {
            $objectBricks = (new Objectbrick\Definition\Listing())->load();
        } catch (Exception) {
            $objectBricks = [];
        }

        foreach ($objectBricks as $definition) {
            try {
                $sets[] = $definition->getFieldDefinitions(['suppressEnrichment' => true]);
            } catch (Exception) {
                continue;
            }
        }

        return $sets;
    }

    private function fieldcollectionCount(): int
    {
        try {
            return count((new Fieldcollection\Definition\Listing())->loadNames());
        } catch (Exception) {
            return 0;
        }
    }

    private function objectbrickCount(): int
    {
        try {
            return count((new Objectbrick\Definition\Listing())->loadNames());
        } catch (Exception) {
            return 0;
        }
    }

    private function customLayoutCount(): int
    {
        try {
            return count((new ClassDefinition\CustomLayout\Listing())->getLayoutDefinitions());
        } catch (Exception) {
            return 0;
        }
    }

    /**
     * Routed through {@see SnapshotQueryRunner} like every other collector, so a classification
     * store with millions of keys is aborted at the per-statement cap instead of stalling the
     * maintenance run.
     */
    private function tableCount(string $table): int
    {
        try {
            $count = $this->queryRunner->fetchOne(
                'SELECT COUNT(*) FROM ' . $this->queryRunner->quoteIdentifier($table)
            );

            return is_numeric($count) ? (int)$count : 0;
        } catch (Exception) {
            return 0;
        }
    }
}
