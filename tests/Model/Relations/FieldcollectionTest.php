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

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Definition;
use Pimcore\Model\DataObject\RelationTest;
use Pimcore\Model\DataObject\Service;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Class FieldcollectionTest
 *
 * @package Pimcore\Tests\Model\Relations
 *
 * @group model.relations.fieldcollection
 */
class FieldcollectionTest extends ModelTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        TestHelper::cleanUp();
    }

    public function tearDown(): void
    {
        TestHelper::cleanUp();
        parent::tearDown();
    }

    protected function setUpTestClasses(): void
    {
        $this->tester->setupPimcoreClass_RelationTest();
        $this->tester->setupFieldcollection_Unittestfieldcollection();
    }

    public function testRelationFieldInsideFieldCollection(): void
    {
        $target1 = new RelationTest();
        $target1->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target1->setKey('mytarget1');
        $target1->setPublished(true);
        $target1->save();

        $target2 = new RelationTest();
        $target2->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target2->setKey('mytarget2');
        $target2->setPublished(true);
        $target2->save();

        $target3 = new RelationTest();
        $target3->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target3->setKey('mytarget3');
        $target3->setPublished(true);
        $target3->save();

        $object = TestHelper::createEmptyObject();

        //save data for language "en"
        $items = new Fieldcollection();

        $item1 = new FieldCollection\Data\Unittestfieldcollection();
        $item1->setFieldRelation([$target1]);

        $item2 = new FieldCollection\Data\Unittestfieldcollection();
        $item2->setFieldRelation([$target2]);

        $items->add($item1);
        $items->add($item2);

        $object->setFieldcollection($items);
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);

        /** @var Fieldcollection $fc */
        $fc = $object->getFieldcollection();
        $items = $fc->getItems();
        $loadedFieldcollectionItem = $items[1];
        $loadedFieldcollectionItem->setFieldRelation([$target3]);           // target3 instead of target2
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(0);
        $rel = $loadedFieldcollectionItem->getFieldRelation();
        $this->assertEquals($target1->getId(), $rel[0]->getId());

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $rel = $loadedFieldcollectionItem->getFieldRelation();
        $this->assertEquals($target3->getId(), $rel[0]->getId());

        //Flush relations
        $loadedFieldcollectionItem->setFieldRelation(null);
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $rel = $loadedFieldcollectionItem->getFieldRelation();
        $this->assertEquals([], $rel);
    }

    public function testAdvancedRelationFieldInsideFieldCollection(): void
    {
        $object = TestHelper::createEmptyObject();
        $items = new Fieldcollection();

        $target1 = new Asset();
        $target1->setParent(Asset::getByPath('/'));
        $target1->setKey('mytarget1');
        $target1->save();

        $target2 = new Asset();
        $target2->setParent(Asset::getByPath('/'));
        $target2->setKey('mytarget2');
        $target2->save();

        $target3 = new Asset();
        $target3->setParent(Asset::getByPath('/'));
        $target3->setKey('mytarget3');
        $target3->save();

        $item1 = new FieldCollection\Data\Unittestfieldcollection();
        $item1->setAdvancedFieldRelation([new ElementMetadata('metadataUpper', [], $target1)]);

        $item2 = new FieldCollection\Data\Unittestfieldcollection();
        $item2->setAdvancedFieldRelation([new ElementMetadata('metadataUpper', [], $target2)]);

        $items->add($item1);
        $items->add($item2);

        $object->setFieldcollection($items);
        $object->save();

        // Test by deleting the target2 element
        $target2->delete();

        $object = DataObject::getById($object->getId(), ['force' => true]);
        $object->save();

        $object = DataObject::getById($object->getId(), ['force' => true]);
        //check if target1 is still there
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(0);
        $rel = $loadedFieldcollectionItem->getAdvancedFieldRelation();
        $this->assertEquals($target1->getId(), isset($rel[0]) ? $rel[0]->getElementId() : false);

        //check if target2 is removed
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $rel = $loadedFieldcollectionItem->getAdvancedFieldRelation();
        $this->assertEquals(false, isset($rel[0]));
    }

    public function testClearingAdvancedRelationMetadataKeepsOtherFieldCollectionItems(): void
    {
        $object = TestHelper::createEmptyObject();

        $targets = [];
        foreach ([1, 2] as $i) {
            $target = new Asset();
            $target->setParent(Asset::getByPath('/'));
            $target->setKey('metadatatarget' . $i);
            $target->save();
            $targets[] = $target;
        }

        $items = new Fieldcollection();
        foreach (['first', 'second'] as $value) {
            $item = new FieldCollection\Data\Unittestfieldcollection();
            $relations = [];
            foreach ($targets as $target) {
                $metadata = new ElementMetadata('advancedFieldRelation', ['metadataUpper'], $target);
                $metadata->setMetadataUpper($value);
                $relations[] = $metadata;
            }
            $item->setAdvancedFieldRelation($relations);
            $items->add($item);
        }
        $object->setFieldcollection($items);
        $object->save();

        // clear the value of the first relation of the first item only
        $object = DataObject::getById($object->getId(), ['force' => true]);
        $first = $object->getFieldcollection()->get(0);
        $cleared = new ElementMetadata('advancedFieldRelation', ['metadataUpper'], $targets[0]);
        $kept = new ElementMetadata('advancedFieldRelation', ['metadataUpper'], $targets[1]);
        $kept->setMetadataUpper('first');
        $first->setAdvancedFieldRelation([$cleared, $kept]);
        $object->save();

        $object = DataObject::getById($object->getId(), ['force' => true]);
        $first = $object->getFieldcollection()->get(0)->getAdvancedFieldRelation();
        $second = $object->getFieldcollection()->get(1)->getAdvancedFieldRelation();
        $this->assertNull($first[0]->getMetadataUpper());
        $this->assertSame('first', $first[1]->getMetadataUpper());
        $this->assertSame('second', $second[0]->getMetadataUpper());
        $this->assertSame('second', $second[1]->getMetadataUpper());

        $rows = (int) \Pimcore\Db::get()->fetchOne(
            'SELECT COUNT(*) FROM object_metadata_' . $object->getClassId() . " WHERE id = ? AND ownertype = 'fieldcollection'",
            [$object->getId()]
        );
        $this->assertSame(3, $rows, 'Only the cleared row is gone.');
    }

    public function testLocalizedFieldInsideFieldCollection(): void
    {
        $target1 = new RelationTest();
        $target1->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target1->setKey('mytarget1');
        $target1->setPublished(true);
        $target1->save();

        $target2 = new RelationTest();
        $target2->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target2->setKey('mytarget2');
        $target2->setPublished(true);
        $target2->save();

        $target3 = new RelationTest();
        $target3->setParent(Service::createFolderByPath('__test/relationobjects'));
        $target3->setKey('mytarget3');
        $target3->setPublished(true);
        $target3->save();

        $object = TestHelper::createEmptyObject();

        //save data for language "en"
        $items = new Fieldcollection();

        $item1 = new FieldCollection\Data\Unittestfieldcollection();
        $item1->setLinput('textEN', 'en');
        $item1->setLRelation($target1, 'en');

        $item2 = new FieldCollection\Data\Unittestfieldcollection();
        $item2->setLinput('textEN', 'en');
        $item2->setLRelation($target2, 'en');

        $items->add($item1);
        $items->add($item2);

        $object->setFieldcollection($items);
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);

        /** @var Fieldcollection $fc */
        $fc = $object->getFieldcollection();
        $items = $fc->getItems();
        $loadedFieldcollectionItem = $items[1];
        $loadedFieldcollectionItem->setLRelation($target3, 'en');
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(0);
        $rel = $loadedFieldcollectionItem->getLRelation('en');
        $this->assertEquals($target1->getId(), $rel->getId());

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $rel = $loadedFieldcollectionItem->getLRelation('en');
        $this->assertEquals($target3->getId(), $rel->getId());

        //Flush relations
        $loadedFieldcollectionItem->setLRelation(null, 'en');
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $lrel = $loadedFieldcollectionItem->getLRelation('en');
        $this->assertEquals(null, $lrel);
    }

    public function testInputFieldWithNullAndThenDefaultValue(): void
    {
        //empty string
        $item1 = new FieldCollection\Data\Unittestfieldcollection();
        $item1->setLinput('', 'en');

        //null by default
        $item2 = new FieldCollection\Data\Unittestfieldcollection();

        //null set manually
        $item3 = new FieldCollection\Data\Unittestfieldcollection();
        $item3->setLinput(null, 'en');

        //save empty data for language "en"
        $items = new Fieldcollection();
        $items->add($item1);
        $items->add($item2);
        $items->add($item3);

        $object = TestHelper::createEmptyObject();
        $object->setFieldcollection($items);
        $object->save();

        //Reload object from db
        $object = DataObject::getById($object->getId(), ['force' => true]);

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(0);
        $linput = $loadedFieldcollectionItem->getLinput('en');
        $this->assertEquals('', $linput);

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $linput = $loadedFieldcollectionItem->getLinput('en');
        $this->assertEquals(null, $linput);

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(2);
        $linput = $loadedFieldcollectionItem->getLinput('en');
        $this->assertEquals(null, $linput);

        // Add default value afterwards
        $definition = Definition::getByKey('unittestfieldcollection');
        $fieldDefinitions = $definition->getFieldDefinitions();
        $children = $fieldDefinitions['localizedfields']->getChildren();
        foreach ($children as $index => $child) {
            if ($child->getName() == 'linput') {
                $children[$index]->setDefaultValue('1234');
            }
        }
        $fieldDefinitions['localizedfields']->setChildren($children);
        $definition->setFieldDefinitions($fieldDefinitions);
        $definition->save();

        // reload, resave and check
        $object = DataObject::getById($object->getId(), ['force' => true]);

        $items = $object->getFieldcollection();
        // add a ex-novo item that should have default value
        $item4 = new FieldCollection\Data\Unittestfieldcollection();
        $items->add($item4);
        $object->setFieldcollection($items);
        $object->save();
        $object = DataObject::getById($object->getId(), ['force' => true]);

        //check all again
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(0);
        $linput = $loadedFieldcollectionItem->getLinput('en');
        $this->assertEquals('', $linput);

        //stays null, the default value is not applied retroactively
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(1);
        $linput = $loadedFieldcollectionItem->getLinput('en');
        $this->assertEquals(null, $linput);

        //stays null, the default value is not applied retroactively
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(2);
        $linput = $loadedFieldcollectionItem->getLinput('en');
        $this->assertEquals(null, $linput);

        //newly added with no values define taking default value
        $loadedFieldcollectionItem = $object->getFieldcollection()->get(3);
        $linput = $loadedFieldcollectionItem->getLinput('en');
        $this->assertEquals('1234', $linput);

        //retest for a new object with the new definition with a default value
        $items = new Fieldcollection();
        $item1 = new FieldCollection\Data\Unittestfieldcollection();
        $items->add($item1);

        $object = TestHelper::createEmptyObject();
        $object->setFieldcollection($items);
        $object->save();

        $loadedFieldcollectionItem = $object->getFieldcollection()->get(0);
        $linput = $loadedFieldcollectionItem->getLinput('en');
        $this->assertEquals('1234', $linput);
    }

    /**
     * Regression test for PEES-2055: the Object Merger bundle calls
     * Data::getDiffDataForEditMode()/getDiffDataFromEditmode() on the "fieldcollection" field definition.
     * Before the fix, Fieldcollections had no override for these methods and fell back to the default
     * implementation, which round-trips through its own plain getDataForEditmode()/getDataFromEditmode().
     * For a Localizedfields child, that round trip is not symmetric (getDataForEditmode() wraps the data
     * in 'data'/'metaData'/'inherited', but getDataFromEditmode() expects the languages directly), which
     * fataled with "Call to a member function getDataFromEditmode() on null".
     *
     * Builds two objects (source/target) and simulates the merger taking over just the localized
     * "lrelation" entry from source into target - not merely feeding an object's own diff payload back
     * into itself, which would pass even if getDiffDataFromEditmode() silently ignored the submitted
     * data and kept the target's existing value. Also covers that an entry left untouched (fieldinput1
     * at the top level, linput inside localizedfields - both left as the target's own default value in
     * the merged payload, the same way panel.js does for anything not explicitly taken over) correctly
     * carries over unchanged rather than being dropped or corrupted.
     */
    public function testDiffDataRoundTripWithLocalizedFieldInsideFieldCollection(): void
    {
        $relationSource = new RelationTest();
        $relationSource->setParent(Service::createFolderByPath('__test/relationobjects'));
        $relationSource->setKey('peesrelationsource');
        $relationSource->setPublished(true);
        $relationSource->save();

        $relationTarget = new RelationTest();
        $relationTarget->setParent(Service::createFolderByPath('__test/relationobjects'));
        $relationTarget->setKey('peesrelationtarget');
        $relationTarget->setPublished(true);
        $relationTarget->save();

        $sourceObject = $this->createUnittestfieldcollectionObject('source plain', 'sourceText', $relationSource);
        $targetObject = $this->createUnittestfieldcollectionObject('target plain', 'targetText', $relationTarget);

        $fd = $targetObject->getClass()->getFieldDefinition('fieldcollection');
        $this->assertInstanceOf(Fieldcollections::class, $fd);

        // Simulate what object-merger's AdminController does: render the diff payload for both
        // sides of the comparison.
        $targetDiff = $fd->getDiffDataForEditMode($targetObject->getFieldcollection(), $targetObject);
        $sourceDiff = $fd->getDiffDataForEditMode($sourceObject->getFieldcollection(), $sourceObject);
        $this->assertNotEmpty($targetDiff, 'Expected at least one diff entry for the target fieldcollection');
        $this->assertNotEmpty($sourceDiff, 'Expected at least one diff entry for the source fieldcollection');

        $sourceEntriesByKey = [];
        foreach ($sourceDiff as $entry) {
            $sourceEntriesByKey[$entry['key']] = $entry;
        }

        // panel.js always resends every entry, defaulting to the target's own value - only the
        // "lrelation" entry is actually taken over from the source here.
        $merged = $targetDiff;
        foreach ($merged as $i => $entry) {
            $innerFieldName = $entry['data']['subdata']['extData']['fieldname'] ?? null;
            if ($innerFieldName === 'lrelation' && isset($sourceEntriesByKey[$entry['key']])) {
                $merged[$i] = $sourceEntriesByKey[$entry['key']];
            }
        }

        $result = $fd->getDiffDataFromEditmode($merged, $targetObject);

        $this->assertInstanceOf(Fieldcollection::class, $result);
        $resultItem = $result->get(0);

        $this->assertSame(
            $relationSource->getId(),
            $resultItem->getLrelation('en')?->getId(),
            'Taking over the relation entry from the source object must actually apply its value, not just preserve the target\'s'
        );
        $this->assertEquals(
            'targetText',
            $resultItem->getLinput('en'),
            'A localized entry left untouched in the merged payload must keep the target\'s existing value'
        );
        $this->assertEquals(
            'target plain',
            $resultItem->getFieldinput1(),
            'A top-level entry left untouched in the merged payload must keep the target\'s existing value'
        );
    }

    /**
     * Regression test for a structural gap found while reviewing PEES-2055: getDiffDataFromEditmode()
     * used to only ever mutate an item that already existed at the same position and type, so a
     * source-only item (present only on the other side of the comparison) had no matching position on
     * the target and was silently dropped instead of being inserted.
     */
    public function testDiffDataFromEditmodeInsertsItemPresentOnlyOnSourceSide(): void
    {
        $relation = new RelationTest();
        $relation->setParent(Service::createFolderByPath('__test/relationobjects'));
        $relation->setKey('peesrelationinserted');
        $relation->setPublished(true);
        $relation->save();

        $targetObject = $this->createUnittestfieldcollectionObject('target plain', 'targetText', $relation);

        // the source has a second item that the target doesn't have at all
        $sourceObject = TestHelper::createEmptyObject();
        $sourceItems = new Fieldcollection();

        $firstItem = new FieldCollection\Data\Unittestfieldcollection();
        $firstItem->setFieldinput1('target plain');
        $firstItem->setLinput('targetText', 'en');
        $firstItem->setLrelation($relation, 'en');
        $sourceItems->add($firstItem);

        $insertedItem = new FieldCollection\Data\Unittestfieldcollection();
        $insertedItem->setFieldinput1('inserted plain');
        $insertedItem->setLinput('insertedText', 'en');
        $insertedItem->setLrelation($relation, 'en');
        $sourceItems->add($insertedItem);

        // a third, also source-only item that deliberately never sets "linput" at all - its diff
        // entry for that field carries null data, so the inserted item must end up with null too,
        // not with the target's row 0 value for "linput" ("targetText")
        $insertedItemWithoutLinput = new FieldCollection\Data\Unittestfieldcollection();
        $insertedItemWithoutLinput->setFieldinput1('inserted plain 2');
        $insertedItemWithoutLinput->setLrelation($relation, 'en');
        $sourceItems->add($insertedItemWithoutLinput);

        $sourceObject->setFieldcollection($sourceItems);
        $sourceObject->save();
        $sourceObject = DataObject::getById($sourceObject->getId(), ['force' => true]);

        $fd = $targetObject->getClass()->getFieldDefinition('fieldcollection');
        $this->assertInstanceOf(Fieldcollections::class, $fd);

        $targetDiff = $fd->getDiffDataForEditMode($targetObject->getFieldcollection(), $targetObject);
        $sourceDiff = $fd->getDiffDataForEditMode($sourceObject->getFieldcollection(), $sourceObject);

        // simulate the merger exposing the source-only items (oIndex 1 and 2) as extra rows that the
        // user takes over in full, on top of the target's own unchanged row (oIndex 0)
        $insertedEntries = array_values(array_filter(
            $sourceDiff,
            static fn (array $entry): bool => in_array($entry['data']['oIndex'] ?? null, [1, 2], true)
        ));
        $this->assertNotEmpty($insertedEntries, 'Expected diff entries for the source-only items');

        $merged = array_merge($targetDiff, $insertedEntries);

        $result = $fd->getDiffDataFromEditmode($merged, $targetObject);

        $this->assertInstanceOf(Fieldcollection::class, $result);
        $this->assertSame(3, $result->getCount(), 'Taking over source-only items must insert them rather than silently drop them');

        $insertedResultItem = $result->get(1);
        $this->assertSame(
            $relation->getId(),
            $insertedResultItem->getLrelation('en')?->getId(),
            'The inserted item must carry over its relation field'
        );
        $this->assertEquals('inserted plain', $insertedResultItem->getFieldinput1());
        $this->assertEquals('insertedText', $insertedResultItem->getLinput('en'));

        // a source-only item whose own diff never carried a value for "linput" must fall back to the
        // field's own configured default - not to the target's row 0 value ("targetText"), confirming
        // it isn't seeded from an unrelated existing item via an out-of-range index. Read the default
        // from the definition itself rather than hardcoding it: other tests in this class (e.g.
        // testInputFieldWithNullAndThenDefaultValue()) persist a default onto this same shared
        // "unittestfieldcollection" definition as a side effect, so the value in force here depends on
        // what ran before this test - asserting against the live definition keeps this test correct
        // regardless of suite/test order.
        $linputDefault = null;
        foreach (Definition::getByKey('unittestfieldcollection')->getFieldDefinitions()['localizedfields']->getChildren() as $child) {
            if ($child->getName() === 'linput') {
                $linputDefault = $child->getDefaultValue();

                break;
            }
        }
        $this->assertEquals(
            $linputDefault,
            $result->get(2)->getLinput('en'),
            'An inserted item must never inherit a localized value from an unrelated existing item'
        );
        $this->assertEquals('inserted plain 2', $result->get(2)->getFieldinput1());

        // the pre-existing item at position 0 must be untouched
        $this->assertEquals('target plain', $result->get(0)->getFieldinput1());
        $this->assertEquals('targetText', $result->get(0)->getLinput('en'));
    }

    /**
     * Copilot review finding for PEES-2055: an empty source collection contributes zero diff rows,
     * which getDiffDataFromEditmode() could not tell apart from "this field wasn't part of the diff
     * at all" - so taking the field over from an empty side silently left the other side's items
     * untouched instead of clearing them.
     */
    public function testDiffDataFromEditmodeClearsItemsWhenTakingOverAnEmptyCollection(): void
    {
        $relation = new RelationTest();
        $relation->setParent(Service::createFolderByPath('__test/relationobjects'));
        $relation->setKey('peesrelationemptytakeover');
        $relation->setPublished(true);
        $relation->save();

        $targetObject = $this->createUnittestfieldcollectionObject('target plain', 'targetText', $relation);

        // never setting the field at all, then reloading, is how a genuinely empty collection
        // actually arrives in practice: Fieldcollections::load() returns null rather than an empty
        // Fieldcollection instance once there are zero items, so getFieldcollection() here is null,
        // not an empty Fieldcollection
        $sourceObject = TestHelper::createEmptyObject();
        $sourceObject->save();
        $sourceObject = DataObject::getById($sourceObject->getId(), ['force' => true]);
        $this->assertNull($sourceObject->getFieldcollection(), 'A never-populated fieldcollection must load back as null, not an empty collection');

        $fd = $targetObject->getClass()->getFieldDefinition('fieldcollection');
        $this->assertInstanceOf(Fieldcollections::class, $fd);

        // simulate taking the whole field over from source, the way a "take all from this side"
        // merger action would: render source's own diff and decode it directly against the target
        $sourceDiff = $fd->getDiffDataForEditMode($sourceObject->getFieldcollection(), $sourceObject);
        $this->assertNotEmpty($sourceDiff, 'An empty/null collection must still contribute an explicit marker row');

        $result = $fd->getDiffDataFromEditmode($sourceDiff, $targetObject);

        $this->assertInstanceOf(Fieldcollection::class, $result);
        $this->assertSame(0, $result->getCount(), 'Taking over an empty collection must clear the other side\'s items, not leave them untouched');
    }

    /**
     * Copilot review finding for PEES-2055: Localizedfields::getDiffDataFromEditmode() tested a
     * submitted value by truthiness, so a legitimate falsey value (e.g. the string "0") was treated
     * the same as "nothing submitted" and dropped instead of being assigned.
     */
    public function testDiffDataRoundTripPreservesFalseyLocalizedValue(): void
    {
        $relation = new RelationTest();
        $relation->setParent(Service::createFolderByPath('__test/relationobjects'));
        $relation->setKey('peesrelationfalsey');
        $relation->setPublished(true);
        $relation->save();

        $sourceObject = $this->createUnittestfieldcollectionObject('source plain', '0', $relation);
        $targetObject = $this->createUnittestfieldcollectionObject('target plain', 'targetText', $relation);

        $fd = $targetObject->getClass()->getFieldDefinition('fieldcollection');
        $this->assertInstanceOf(Fieldcollections::class, $fd);

        $targetDiff = $fd->getDiffDataForEditMode($targetObject->getFieldcollection(), $targetObject);
        $sourceDiff = $fd->getDiffDataForEditMode($sourceObject->getFieldcollection(), $sourceObject);

        $sourceEntriesByKey = [];
        foreach ($sourceDiff as $entry) {
            $sourceEntriesByKey[$entry['key']] = $entry;
        }

        // take over only the "linput" entry from source, same as the round-trip test above does for
        // "lrelation"
        $merged = $targetDiff;
        foreach ($merged as $i => $entry) {
            $innerFieldName = $entry['data']['subdata']['extData']['fieldname'] ?? null;
            if ($innerFieldName === 'linput' && isset($sourceEntriesByKey[$entry['key']])) {
                $merged[$i] = $sourceEntriesByKey[$entry['key']];
            }
        }

        $result = $fd->getDiffDataFromEditmode($merged, $targetObject);

        $this->assertInstanceOf(Fieldcollection::class, $result);
        $this->assertSame(
            '0',
            $result->get(0)->getLinput('en'),
            'A legitimate falsey localized value ("0") must round-trip, not be dropped as if nothing was submitted'
        );
    }

    private function createUnittestfieldcollectionObject(string $plainValue, string $localizedValue, RelationTest $relation): DataObject\Concrete
    {
        $object = TestHelper::createEmptyObject();

        $items = new Fieldcollection();
        $item = new FieldCollection\Data\Unittestfieldcollection();
        $item->setFieldinput1($plainValue);
        $item->setLinput($localizedValue, 'en');
        $item->setLrelation($relation, 'en');
        $items->add($item);
        $object->setFieldcollection($items);
        $object->save();

        return DataObject::getById($object->getId(), ['force' => true]);
    }
}
