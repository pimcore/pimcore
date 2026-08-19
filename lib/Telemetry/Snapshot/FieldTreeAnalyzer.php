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

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\ClassDefinition\Data\CalculatedValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;

/**
 * Walks a data-model field-definition tree and returns structural {@see FieldTreeMetrics}.
 *
 * Recursion descends only into the two inline container field types - {@see Block} and
 * {@see Localizedfields} - via their `getChildren()`. Field collections and object bricks are NOT
 * followed from a class here (they reference external definitions); the collector analyzes those
 * definitions separately so their fields are counted exactly once. Reads structure only, never
 * `getName()` values.
 *
 * @internal
 */
final class FieldTreeAnalyzer
{
    /**
     * @param array<int|string, mixed> $fieldDefinitions
     */
    public function analyze(array $fieldDefinitions): FieldTreeMetrics
    {
        return $this->walk($fieldDefinitions, 1);
    }

    /**
     * @param array<int|string, mixed> $fieldDefinitions
     */
    private function walk(array $fieldDefinitions, int $depth): FieldTreeMetrics
    {
        $metrics = new FieldTreeMetrics();

        foreach ($fieldDefinitions as $fieldDefinition) {
            if (!$fieldDefinition instanceof Data) {
                continue;
            }

            $isAdvancedRelation = $fieldDefinition instanceof AdvancedManyToManyRelation
                || $fieldDefinition instanceof AdvancedManyToManyObjectRelation;

            $metrics = $metrics->combine(new FieldTreeMetrics(
                fieldCount: 1,
                maxDepth: $depth,
                typeUsage: [$fieldDefinition->getFieldType() => 1],
                relationFieldCount: $fieldDefinition instanceof AbstractRelations ? 1 : 0,
                usesLocalizedfields: $fieldDefinition instanceof Localizedfields,
                usesBlocks: $fieldDefinition instanceof Block,
                usesClassificationstore: $fieldDefinition instanceof Classificationstore,
                usesCalculatedValue: $fieldDefinition instanceof CalculatedValue,
                usesAdvancedRelations: $isAdvancedRelation,
            ));

            if ($fieldDefinition instanceof Localizedfields || $fieldDefinition instanceof Block) {
                $metrics = $metrics->combine($this->walk($fieldDefinition->getChildren(), $depth + 1));
            }
        }

        return $metrics;
    }
}
