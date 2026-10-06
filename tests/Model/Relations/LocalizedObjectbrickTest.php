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

namespace Pimcore\Tests\Model\Relations;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\LocalizedBrickRelation;
use Pimcore\Model\DataObject\Objectbrick\Data\LocalizedRelationBrickA;
use Pimcore\Model\DataObject\Objectbrick\Data\LocalizedRelationBrickB;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\DataObject\Service;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Two object bricks in the same container whose localized fields share field names
 * must not read or overwrite each other's relations.
 *
 * @see https://github.com/pimcore/pimcore/issues/9701
 *
 * @group model.relations.objectbrick
 */
class LocalizedObjectbrickTest extends ModelTestCase
{
    /** @var RelationTest[] */
    private array $targets = [];

    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();

        $this->targets = [];
        for ($i = 0; $i < 4; $i++) {
            $target = new RelationTest();
            $target->setParent(Service::createFolderByPath('__test/relationobjects'));
            $target->setKey('target-' . $i);
            $target->setPublished(true);
            $target->save();
            $this->targets[] = $target;
        }
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    protected function setUpTestClasses(): void
    {
        $this->tester->setupPimcoreClass_RelationTest();
        $this->tester->setupPimcoreClass_LocalizedBrickRelation();

        // two bricks with identical localized field definitions in the same container
        foreach (['LocalizedRelationBrickA', 'LocalizedRelationBrickB'] as $brick) {
            $this->tester->setupObjectbrick_LazyLoadingLocalizedTest(
                $brick,
                'relations/objectbrick_' . $brick . '_export.json',
                [['classname' => 'LocalizedBrickRelation', 'fieldname' => 'bricks']]
            );
        }
    }

    public function testSharedLocalizedRelationFieldNamesDoNotClash(): void
    {
        [$t0, $t1, $t2, $t3] = $this->targets;

        $object = $this->createDataObject();
        $first = new LocalizedRelationBrickA($object);
        $first->setLrelations([$t0], 'en');
        $first->setLrelation($t0, 'en');
        $second = new LocalizedRelationBrickB($object);
        $second->setLrelations([$t1], 'en');
        $second->setLrelation($t1, 'en');
        $object->getBricks()->setLocalizedRelationBrickA($first);
        $object->getBricks()->setLocalizedRelationBrickB($second);
        $object->save();

        $object = LocalizedBrickRelation::getById($object->getId(), ['force' => true]);
        $bricks = $object->getBricks();
        $this->assertRelationIds([$t0->getId()], $bricks->getLocalizedRelationBrickA()->getLrelations('en'));
        $this->assertRelationIds([$t1->getId()], $bricks->getLocalizedRelationBrickB()->getLrelations('en'));
        $this->assertSame($t0->getId(), $bricks->getLocalizedRelationBrickA()->getLrelation('en')?->getId());
        $this->assertSame($t1->getId(), $bricks->getLocalizedRelationBrickB()->getLrelation('en')?->getId());

        // change only the first brick, the second one must keep its relations
        $bricks->getLocalizedRelationBrickA()->setLrelations([$t2, $t3], 'en');
        $bricks->getLocalizedRelationBrickA()->setLrelation($t2, 'en');
        $object->save();

        $object = LocalizedBrickRelation::getById($object->getId(), ['force' => true]);
        $bricks = $object->getBricks();
        $first = $bricks->getLocalizedRelationBrickA();
        $this->assertRelationIds([$t2->getId(), $t3->getId()], $first->getLrelations('en'));
        $this->assertRelationIds([$t1->getId()], $bricks->getLocalizedRelationBrickB()->getLrelations('en'));
        $this->assertSame($t2->getId(), $first->getLrelation('en')?->getId());
        $this->assertSame($t1->getId(), $bricks->getLocalizedRelationBrickB()->getLrelation('en')?->getId());

        // clear the first brick's relations, the second one must keep its relations
        $first->setLrelations([], 'en');
        $object->save();

        $object = LocalizedBrickRelation::getById($object->getId(), ['force' => true]);
        $bricks = $object->getBricks();
        $this->assertRelationIds([], $bricks->getLocalizedRelationBrickA()->getLrelations('en'));
        $this->assertRelationIds([$t1->getId()], $bricks->getLocalizedRelationBrickB()->getLrelations('en'));
    }

    public function testSharedLocalizedAdvancedRelationFieldNamesDoNotClash(): void
    {
        [$t0, $t1, $t2] = $this->targets;

        $object = $this->createDataObject();
        $first = new LocalizedRelationBrickA($object);
        $first->setLadvancedObjects([$this->objectMetadata($t0, 'first')], 'en');
        $first->setLadvancedRelations([$this->elementMetadata($t0, 'first')], 'en');
        $second = new LocalizedRelationBrickB($object);
        $second->setLadvancedObjects([$this->objectMetadata($t1, 'second')], 'en');
        $second->setLadvancedRelations([$this->elementMetadata($t1, 'second')], 'en');
        $object->getBricks()->setLocalizedRelationBrickA($first);
        $object->getBricks()->setLocalizedRelationBrickB($second);
        $object->save();

        // change only the first brick, the second one must keep its relations and metadata
        $object = LocalizedBrickRelation::getById($object->getId(), ['force' => true]);
        $first = $object->getBricks()->getLocalizedRelationBrickA();
        $first->setLadvancedObjects([$this->objectMetadata($t2, 'first-changed')], 'en');
        $first->setLadvancedRelations([$this->elementMetadata($t2, 'first-changed')], 'en');
        $object->save();

        $object = LocalizedBrickRelation::getById($object->getId(), ['force' => true]);
        $first = $object->getBricks()->getLocalizedRelationBrickA();
        $second = $object->getBricks()->getLocalizedRelationBrickB();

        $this->assertMetadata([$t2->getId() => 'first-changed'], $first->getLadvancedObjects('en'));
        $this->assertMetadata([$t2->getId() => 'first-changed'], $first->getLadvancedRelations('en'));
        $this->assertMetadata([$t1->getId() => 'second'], $second->getLadvancedObjects('en'));
        $this->assertMetadata([$t1->getId() => 'second'], $second->getLadvancedRelations('en'));
    }

    private function createDataObject(): LocalizedBrickRelation
    {
        $object = new LocalizedBrickRelation();
        $object->setParentId(1);
        $object->setKey('localized-brick-relations');
        $object->setPublished(true);

        return $object;
    }

    private function objectMetadata(DataObject\Concrete $target, string $value): ObjectMetadata
    {
        $metadata = new ObjectMetadata('ladvancedObjects', ['metadata'], $target);
        $metadata->setMetadata($value);

        return $metadata;
    }

    private function elementMetadata(DataObject\Concrete $target, string $value): ElementMetadata
    {
        $metadata = new ElementMetadata('ladvancedRelations', ['metadata'], $target);
        $metadata->setMetadata($value);

        return $metadata;
    }

    /**
     * @param int[] $expectedIds
     * @param DataObject\AbstractObject[] $relations
     */
    private function assertRelationIds(array $expectedIds, array $relations): void
    {
        $this->assertSame($expectedIds, array_map(static fn ($relation) => $relation->getId(), $relations));
    }

    /**
     * @param array<int, string> $expected element id => metadata value
     * @param array<ObjectMetadata|ElementMetadata> $relations
     */
    private function assertMetadata(array $expected, array $relations): void
    {
        $actual = [];
        foreach ($relations as $relation) {
            $actual[$relation->getElement()->getId()] = $relation->getMetadata();
        }
        $this->assertSame($expected, $actual);
    }
}
