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

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\ClassDefinition\Data\CalculatedValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Telemetry\Snapshot\FieldTreeAnalyzer;
use Pimcore\Tests\Support\Test\TestCase;

class FieldTreeAnalyzerTest extends TestCase
{
    public function testFlatTreeCountsFieldsTypesAndRelations(): void
    {
        $fields = [
            $this->field(Input::class, 'title'),
            $this->field(Input::class, 'sku'),
            $this->field(ManyToOneRelation::class, 'brand'),
        ];

        $metrics = (new FieldTreeAnalyzer())->analyze($fields);

        $this->assertSame(3, $metrics->fieldCount);
        $this->assertSame(1, $metrics->maxDepth);
        $this->assertSame(2, $metrics->distinctTypeCount()); // input, manyToOneRelation
        $this->assertSame(1, $metrics->relationFieldCount);
        $this->assertFalse($metrics->usesBlocks);
        $this->assertFalse($metrics->usesAdvancedRelations);
    }

    public function testAdvancedRelationSetsFlag(): void
    {
        $metrics = (new FieldTreeAnalyzer())->analyze([
            $this->field(AdvancedManyToManyRelation::class, 'crossSell'),
        ]);

        $this->assertSame(1, $metrics->relationFieldCount);
        $this->assertTrue($metrics->usesAdvancedRelations);
    }

    public function testNestedContainersIncreaseDepthAndCounts(): void
    {
        $street = $this->field(Input::class, 'street');
        // Localizedfields::setName() enforces the fixed name 'localizedfields' (see
        // Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields::setName()); it is a
        // singleton field within a class, so it cannot be named anything else.
        $localized = $this->container(Localizedfields::class, 'localizedfields', [$street]);
        $caption = $this->field(Input::class, 'caption');
        $block = $this->container(Block::class, 'items', [$caption, $localized]);

        $fields = [$this->field(Input::class, 'title'), $block];

        $metrics = (new FieldTreeAnalyzer())->analyze($fields);

        // title(d1) + block(d1) + caption(d2) + localizedfields(d2) + street(d3)
        $this->assertSame(5, $metrics->fieldCount);
        $this->assertSame(3, $metrics->maxDepth);
        $this->assertTrue($metrics->usesBlocks);
        $this->assertTrue($metrics->usesLocalizedfields);
        $this->assertSame(3, $metrics->distinctTypeCount()); // input, block, localizedfields
    }

    public function testClassificationstoreFieldSetsFlag(): void
    {
        $metrics = (new FieldTreeAnalyzer())->analyze([
            $this->field(Classificationstore::class, 'cs'),
        ]);

        $this->assertTrue($metrics->usesClassificationstore);
    }

    public function testCalculatedValueFieldSetsFlag(): void
    {
        $metrics = (new FieldTreeAnalyzer())->analyze([
            $this->field(CalculatedValue::class, 'calc'),
        ]);

        $this->assertTrue($metrics->usesCalculatedValue);
    }

    public function testEmptyFieldListReturnsAllZeroMetrics(): void
    {
        $metrics = (new FieldTreeAnalyzer())->analyze([]);

        $this->assertSame(0, $metrics->fieldCount);
        $this->assertSame(0, $metrics->maxDepth);
        $this->assertSame(0, $metrics->distinctTypeCount());
        $this->assertSame(0, $metrics->relationFieldCount);
        $this->assertFalse($metrics->usesLocalizedfields);
        $this->assertFalse($metrics->usesBlocks);
        $this->assertFalse($metrics->usesClassificationstore);
        $this->assertFalse($metrics->usesCalculatedValue);
        $this->assertFalse($metrics->usesAdvancedRelations);
    }

    public function testNonDataEntryIsSkipped(): void
    {
        $metrics = (new FieldTreeAnalyzer())->analyze([
            'not-a-field',
            $this->field(Input::class, 'title'),
        ]);

        $this->assertSame(1, $metrics->fieldCount);
    }

    private function field(string $class, string $name): Data
    {
        /** @var Data $field */
        $field = new $class();
        $field->setName($name);

        return $field;
    }

    /**
     * @param list<Data> $children
     */
    private function container(string $class, string $name, array $children): Data
    {
        /** @var Data&Block|Data&Localizedfields $field */
        $field = new $class();
        $field->setName($name);
        $field->setChildren($children);

        return $field;
    }
}
