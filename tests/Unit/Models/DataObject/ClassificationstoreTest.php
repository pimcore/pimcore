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

namespace Pimcore\Tests\Unit\Model\DataObject;

use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionMethod;

class ClassificationstoreTest extends TestCase
{
    /**
     * Regression: the recursive call inside mergeArrays() was passing arguments
     * in the wrong order ($a2[$key], $value) instead of ($value, $a2[$key]),
     * which flipped the merge precedence for nested arrays so that parent[0]
     * overwrote child[0] in numeric arrays.
     *
     * Realistic Classification Store structure: groupId → keyId → locale/default.
     */
    public function testMergeArraysPreservesChildMultiselectValues(): void
    {
        $store = new Classificationstore();

        $method = new ReflectionMethod($store, 'mergeArrays');
        $method->setAccessible(true);

        // Simulate: $a1 = child (accumulated fieldsArray), $a2 = parent items
        // Child should win on conflicts
        $child = [
            1 => [  // groupId
                100 => [  // keyId
                    'default' => ['Breathable', 'Coated', 'Seamless'],
                ],
            ],
        ];

        $parent = [
            1 => [
                100 => [
                    'default' => ['Abrasion-Resistant'],
                ],
            ],
        ];

        $result = $method->invoke($store, $child, $parent);

        // Child values should be preserved, not overwritten by parent
        $this->assertSame(
            ['Breathable', 'Coated', 'Seamless'],
            $result[1][100]['default'],
            'Child multiselect values should not be overwritten by parent values during inheritance merge'
        );
    }

    public function testMergeArraysChildInheritsParentOnlyKeys(): void
    {
        $store = new Classificationstore();

        $method = new ReflectionMethod($store, 'mergeArrays');
        $method->setAccessible(true);

        // Child has one key, parent has a different key — both should appear
        $child = [
            1 => [
                100 => ['default' => ['Red']],
            ],
        ];

        $parent = [
            1 => [
                200 => ['default' => ['Steel']],
            ],
        ];

        $result = $method->invoke($store, $child, $parent);

        $this->assertSame(['Red'], $result[1][100]['default']);
        $this->assertSame(['Steel'], $result[1][200]['default']);
    }
}