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

use Pimcore\Db;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Data\UrlSlug;
use Pimcore\Model\DataObject\LocalizedBrickRelation;
use Pimcore\Model\DataObject\Objectbrick\Data\LocalizedRelationBrickA;
use Pimcore\Model\DataObject\Objectbrick\Data\LocalizedRelationBrickB;
use Pimcore\Model\DataObject\Objectbrick\Data\LocalizedRelBrick_C;
use Pimcore\Model\DataObject\Objectbrick\Data\LocalizedRelBrickXC;
use Pimcore\Model\DataObject\Objectbrick\Data\LocalizedSlugBrickA;
use Pimcore\Model\DataObject\Objectbrick\Data\LocalizedSlugBrickB;
use Pimcore\Model\DataObject\Objectbrick\Data\PlainRelationBrickA;
use Pimcore\Model\DataObject\Objectbrick\Data\PlainRelationBrickB;
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

        $container = [['classname' => 'LocalizedBrickRelation', 'fieldname' => 'bricks']];

        // bricks with identical localized field definitions in the same container; the last two keys only differ
        // by "_", which is a wildcard in SQL LIKE
        foreach (['LocalizedRelationBrickA', 'LocalizedRelationBrickB', 'LocalizedRelBrick_C', 'LocalizedRelBrickXC'] as $brick) {
            $this->tester->setupObjectbrick_LazyLoadingLocalizedTest(
                $brick,
                'relations/objectbrick_' . $brick . '_export.json',
                $container
            );
        }
        // bricks with the same (not localized) advanced relation fields
        foreach (['PlainRelationBrickA', 'PlainRelationBrickB'] as $brick) {
            $this->tester->setupObjectbrick_LazyLoadingTest(
                $brick,
                'relations/objectbrick_' . $brick . '_export.json',
                $container
            );
        }
        // bricks with a localized slug, registered after bricks whose localized fields have no slug
        foreach (['LocalizedSlugBrickA', 'LocalizedSlugBrickB'] as $brick) {
            $this->tester->setupObjectbrick_LocalizedSlugTest(
                $brick,
                'relations/objectbrick_' . $brick . '_export.json',
                $container
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

    public function testMetadataOfOtherLanguagesAndBricksIsKept(): void
    {
        [$t0, $t1, $t2] = $this->targets;

        $object = $this->createDataObject();
        foreach (['A' => new LocalizedRelationBrickA($object), 'B' => new LocalizedRelationBrickB($object)] as $tag => $brick) {
            foreach (['en' => $t0, 'de' => $t1] as $language => $target) {
                $brick->setLadvancedObjects([$this->objectMetadata($target, "$tag-$language")], $language);
                $brick->setLadvancedRelations([$this->elementMetadata($target, "$tag-$language")], $language);
            }
            $object->getBricks()->set($brick->getType(), $brick);
        }
        $object->save();

        // change only the English values of the first brick
        $object = LocalizedBrickRelation::getById($object->getId(), ['force' => true]);
        $first = $object->getBricks()->getLocalizedRelationBrickA();
        $first->setLadvancedObjects([$this->objectMetadata($t2, 'A-en-changed')], 'en');
        $first->setLadvancedRelations([$this->elementMetadata($t2, 'A-en-changed')], 'en');
        $object->save();

        $object = LocalizedBrickRelation::getById($object->getId(), ['force' => true]);
        $first = $object->getBricks()->getLocalizedRelationBrickA();
        $second = $object->getBricks()->getLocalizedRelationBrickB();
        $this->assertMetadata([$t2->getId() => 'A-en-changed'], $first->getLadvancedObjects('en'));
        $this->assertMetadata([$t2->getId() => 'A-en-changed'], $first->getLadvancedRelations('en'));
        $this->assertMetadata([$t1->getId() => 'A-de'], $first->getLadvancedObjects('de'));
        $this->assertMetadata([$t1->getId() => 'A-de'], $first->getLadvancedRelations('de'));
        foreach (['en' => $t0, 'de' => $t1] as $language => $target) {
            $this->assertMetadata([$target->getId() => "B-$language"], $second->getLadvancedObjects($language));
            $this->assertMetadata([$target->getId() => "B-$language"], $second->getLadvancedRelations($language));
        }
        // one row per field and language, nothing left behind
        $this->assertSame(4, $this->countRows('object_metadata', 'id', $object->getId(), 'LocalizedRelationBrickA'));
        $this->assertSame(4, $this->countRows('object_metadata', 'id', $object->getId(), 'LocalizedRelationBrickB'));
    }

    public function testUnderscoreInBrickKeyDoesNotMatchOtherBricks(): void
    {
        [$t0, $t1] = $this->targets;

        $object = $this->createDataObject();
        foreach ([new LocalizedRelBrick_C($object), new LocalizedRelBrickXC($object)] as $brick) {
            $brick->setLadvancedObjects([$this->objectMetadata($t0, $brick->getType())], 'en');
            $brick->setLadvancedRelations([$this->elementMetadata($t0, $brick->getType())], 'en');
            $object->getBricks()->set($brick->getType(), $brick);
        }
        $object->save();

        $object = LocalizedBrickRelation::getById($object->getId(), ['force' => true]);
        $brick = $object->getBricks()->getLocalizedRelBrick_C();
        $brick->setLadvancedObjects([$this->objectMetadata($t1, 'changed')], 'en');
        $brick->setLadvancedRelations([$this->elementMetadata($t1, 'changed')], 'en');
        $object->save();

        $object = LocalizedBrickRelation::getById($object->getId(), ['force' => true]);
        $other = $object->getBricks()->getLocalizedRelBrickXC();
        $this->assertMetadata([$t0->getId() => 'LocalizedRelBrickXC'], $other->getLadvancedObjects('en'));
        $this->assertMetadata([$t0->getId() => 'LocalizedRelBrickXC'], $other->getLadvancedRelations('en'));
        $this->assertMetadata([$t1->getId() => 'changed'], $object->getBricks()->getLocalizedRelBrick_C()->getLadvancedRelations('en'));
    }

    public static function dirtyDetectionProvider(): array
    {
        return [
            'dirty detection enabled' => [false],
            'dirty detection disabled' => [true],
        ];
    }

    /**
     * @dataProvider dirtyDetectionProvider
     */
    public function testRemovingABrickRemovesItsLocalizedData(bool $disableDirtyDetection): void
    {
        [$t0, $t1] = $this->targets;

        $object = $this->createDataObject();
        foreach (['A' => new LocalizedRelationBrickA($object), 'B' => new LocalizedRelationBrickB($object)] as $tag => $brick) {
            $target = $tag === 'A' ? $t0 : $t1;
            $brick->setLrelations([$target], 'en');
            $brick->setLadvancedObjects([$this->objectMetadata($target, $tag)], 'en');
            $brick->setLadvancedRelations([$this->elementMetadata($target, $tag)], 'en');
            $object->getBricks()->set($brick->getType(), $brick);
        }
        $object->save();
        $id = $object->getId();

        $object = LocalizedBrickRelation::getById($id, ['force' => true]);
        $object->getBricks()->getLocalizedRelationBrickB()->setDoDelete(true);
        if ($disableDirtyDetection) {
            DataObject::disableDirtyDetection();
        }

        try {
            $object->save();
        } finally {
            DataObject::enableDirtyDetection();
        }

        $object = LocalizedBrickRelation::getById($id, ['force' => true]);
        $this->assertNull($object->getBricks()->getLocalizedRelationBrickB());
        $this->assertSame(0, $this->countRows('object_relations', 'src_id', $id, 'LocalizedRelationBrickB'));
        $this->assertSame(0, $this->countRows('object_metadata', 'id', $id, 'LocalizedRelationBrickB'));

        $first = $object->getBricks()->getLocalizedRelationBrickA();
        $this->assertRelationIds([$t0->getId()], $first->getLrelations('en'));
        $this->assertMetadata([$t0->getId() => 'A'], $first->getLadvancedObjects('en'));
        $this->assertMetadata([$t0->getId() => 'A'], $first->getLadvancedRelations('en'));

        // adding the brick again must not bring the removed values back
        $object->getBricks()->setLocalizedRelationBrickB(new LocalizedRelationBrickB($object));
        $object->save();
        $object = LocalizedBrickRelation::getById($id, ['force' => true]);
        $second = $object->getBricks()->getLocalizedRelationBrickB();
        $this->assertRelationIds([], $second->getLrelations('en'));
        $this->assertSame([], $second->getLadvancedRelations('en'));
    }

    public function testRemovingABrickKeepsTheMetadataOfOtherBricks(): void
    {
        [$t0, $t1] = $this->targets;

        $object = $this->createDataObject();
        foreach ([new PlainRelationBrickA($object), new PlainRelationBrickB($object)] as $brick) {
            $metadata = new ElementMetadata('advancedRelations', ['metadataUpper'], $t0);
            $metadata->setMetadataUpper($brick->getType());
            $brick->setAdvancedRelations([$metadata]);
            $object->getBricks()->set($brick->getType(), $brick);
        }
        $object->save();

        $object = LocalizedBrickRelation::getById($object->getId(), ['force' => true]);
        $object->getBricks()->getPlainRelationBrickB()->setDoDelete(true);
        $object->save();

        $object = LocalizedBrickRelation::getById($object->getId(), ['force' => true]);
        $this->assertNull($object->getBricks()->getPlainRelationBrickB());
        $relations = $object->getBricks()->getPlainRelationBrickA()->getAdvancedRelations();
        $this->assertCount(1, $relations);
        $this->assertSame('PlainRelationBrickA', $relations[0]->getMetadataUpper());
    }

    public function testSlugsInBricksAreResolvedAndRemovedPerBrick(): void
    {
        $slugA = '/localized-slug-brick-a';
        $slugB = '/localized-slug-brick-b';

        $object = $this->createDataObject();
        $first = new LocalizedSlugBrickA($object);
        $first->setLslug([new UrlSlug($slugA)], 'en');
        $second = new LocalizedSlugBrickB($object);
        $second->setLslug([new UrlSlug($slugB)], 'en');
        $object->getBricks()->setLocalizedSlugBrickA($first);
        $object->getBricks()->setLocalizedSlugBrickB($second);
        $object->save();
        $id = $object->getId();

        // the field definition is looked up in the slug's own brick; when it is not found the slug is deleted
        foreach ([$slugA, $slugB] as $slug) {
            $this->assertSame('App\Controller\TestController::slugAction', UrlSlug::resolveSlug($slug)?->getAction());
            $this->assertSame(1, $this->countSlugs($slug));
        }

        // removing one brick keeps the slugs of the other one
        $object = LocalizedBrickRelation::getById($id, ['force' => true]);
        $object->getBricks()->getLocalizedSlugBrickA()->setDoDelete(true);
        $object->save();

        $this->assertSame(0, $this->countSlugs($slugA));
        $this->assertSame(1, $this->countSlugs($slugB));
        $object = LocalizedBrickRelation::getById($id, ['force' => true]);
        $this->assertSame(
            [$slugB],
            array_map(static fn (UrlSlug $slug) => $slug->getSlug(), $object->getBricks()->getLocalizedSlugBrickB()->getLslug('en'))
        );
    }

    private function createDataObject(): LocalizedBrickRelation
    {
        $object = new LocalizedBrickRelation();
        $object->setParentId(1);
        $object->setKey('localized-brick-relations');
        $object->setPublished(true);

        return $object;
    }

    /**
     * Rows of a brick's localized fields in object_relations_* or object_metadata_*.
     */
    private function countRows(string $table, string $idColumn, int $objectId, string $brick): int
    {
        return (int) Db::get()->fetchOne(
            'SELECT COUNT(*) FROM ' . $table . '_' . LocalizedBrickRelation::classId()
            . ' WHERE ' . $idColumn . " = ? AND ownertype = 'localizedfield' AND ownername = ?",
            [$objectId, '/objectbrick~bricks/' . $brick . '/localizedfield~localizedfield']
        );
    }

    private function countSlugs(string $slug): int
    {
        return (int) Db::get()->fetchOne('SELECT COUNT(*) FROM ' . UrlSlug::TABLE_NAME . ' WHERE slug = ?', [$slug]);
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
