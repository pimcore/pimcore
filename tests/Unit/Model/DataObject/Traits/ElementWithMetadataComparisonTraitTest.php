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

namespace Pimcore\Tests\Unit\Model\DataObject\Traits;

use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Unit tests for ElementWithMetadataComparisonTrait::isEqual().
 *
 * The containers are built without an element on purpose: getElement() resolves through the
 * element service, and the behaviour under test here is how entries are dispatched for
 * comparison, not how elements are loaded.
 *
 * @internal
 */
class ElementWithMetadataComparisonTraitTest extends TestCase
{
    protected function needsDb(): bool
    {
        return false;
    }

    private function elementMetadata(array $data, array $columns = ['role']): ElementMetadata
    {
        $metadata = new ElementMetadata('relation', $columns);
        $metadata->setData($data);

        return $metadata;
    }

    private function objectMetadata(array $data, array $columns = ['role']): ObjectMetadata
    {
        $metadata = new ObjectMetadata('relation', $columns);
        $metadata->setData($data);

        return $metadata;
    }

    public function testEqualContainersAreEqual(): void
    {
        $fd = new AdvancedManyToManyRelation();

        $this->assertTrue($fd->isEqual(
            [$this->elementMetadata(['role' => 'hero'])],
            [$this->elementMetadata(['role' => 'hero'])]
        ));
    }

    public function testDifferingMetadataIsNotEqual(): void
    {
        $fd = new AdvancedManyToManyRelation();

        $this->assertFalse($fd->isEqual(
            [$this->elementMetadata(['role' => 'hero'])],
            [$this->elementMetadata(['role' => 'thumbnail'])]
        ));
    }

    /**
     * A value can reach isEqual() as a plain element path rather than a container. getElement()
     * was called on the string, and the comparison fatalled with a TypeError.
     */
    public function testPathEntriesAreComparedDirectly(): void
    {
        $fd = new AdvancedManyToManyRelation();

        $this->assertTrue($fd->isEqual(['/images/one.jpg'], ['/images/one.jpg']));
        $this->assertFalse($fd->isEqual(['/images/one.jpg'], ['/images/two.jpg']));
    }

    /**
     * The comparison has to continue past an equal pair: returning early would report a
     * collection whose first entries match as unchanged, whatever follows them.
     */
    public function testALaterDifferenceIsStillReported(): void
    {
        $fd = new AdvancedManyToManyRelation();

        $this->assertFalse($fd->isEqual(
            ['/images/one.jpg', '/images/two.jpg'],
            ['/images/one.jpg', '/images/three.jpg']
        ));
    }

    public function testAContainerAndAPathAreNotEqual(): void
    {
        $fd = new AdvancedManyToManyRelation();

        $this->assertFalse($fd->isEqual(
            [$this->elementMetadata(['role' => 'hero'])],
            ['/images/one.jpg']
        ));
    }

    /**
     * ObjectMetadata is a sibling of ElementMetadata, not a subclass, so a guard written as
     * `instanceof ElementMetadata` would push every AdvancedManyToManyObjectRelation value into
     * the plain-value branch and compare whole containers instead of element and metadata.
     *
     * The two containers below differ only in their column configuration, which is part of the
     * field definition rather than of the value: comparing them as containers says equal,
     * comparing them as plain values says different.
     */
    public function testObjectMetadataIsComparedAsAContainer(): void
    {
        $fd = new AdvancedManyToManyObjectRelation();

        $this->assertTrue($fd->isEqual(
            [$this->objectMetadata(['role' => 'hero'], ['role'])],
            [$this->objectMetadata(['role' => 'hero'], ['role', 'comment'])]
        ));
        $this->assertFalse($fd->isEqual(
            [$this->objectMetadata(['role' => 'hero'])],
            [$this->objectMetadata(['role' => 'thumbnail'])]
        ));
    }
}
