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

use Pimcore\Telemetry\Snapshot\FieldTreeMetrics;
use Pimcore\Tests\Support\Test\TestCase;

class FieldTreeMetricsTest extends TestCase
{
    public function testEmptyMetricsAreZero(): void
    {
        $metrics = new FieldTreeMetrics();

        $this->assertSame(0, $metrics->fieldCount);
        $this->assertSame(0, $metrics->maxDepth);
        $this->assertSame(0, $metrics->distinctTypeCount());
        $this->assertSame([], $metrics->typeUsage);
    }

    public function testCombineSumsCountsMaxesDepthMergesTypesAndOrsFlags(): void
    {
        $a = new FieldTreeMetrics(
            fieldCount: 3,
            maxDepth: 2,
            typeUsage: ['input' => 2, 'block' => 1],
            relationFieldCount: 1,
            usesBlocks: true,
        );
        $b = new FieldTreeMetrics(
            fieldCount: 2,
            maxDepth: 4,
            typeUsage: ['input' => 1, 'manyToOneRelation' => 1],
            relationFieldCount: 1,
            usesLocalizedfields: true,
            usesAdvancedRelations: true,
        );

        $c = $a->combine($b);

        $this->assertSame(5, $c->fieldCount);
        $this->assertSame(4, $c->maxDepth);
        $this->assertSame(['input' => 3, 'block' => 1, 'manyToOneRelation' => 1], $c->typeUsage);
        $this->assertSame(2, $c->relationFieldCount);
        $this->assertSame(3, $c->distinctTypeCount());
        $this->assertTrue($c->usesBlocks);
        $this->assertTrue($c->usesLocalizedfields);
        $this->assertTrue($c->usesAdvancedRelations);
        $this->assertFalse($c->usesClassificationstore);
        $this->assertFalse($c->usesCalculatedValue);
    }
}
