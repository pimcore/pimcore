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

namespace Pimcore\Tests\Model\DataObject;

use Exception;
use Normalizer;
use Pimcore\Db;
use Pimcore\Db\Helper as DbHelper;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\DataObject\Service as DataObjectService;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Class ObjectTest
 *
 * @package Pimcore\Tests\Model\DataObject
 *
 * @group model.dataobject.object
 */
class ObjectTest extends ModelTestCase
{
    /**
     * Verifies that an object with the same parent ID cannot be created.
     */
    public function testParentIdentical(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("ParentID and ID are identical, an element can't be the parent of itself in the tree.");
        $savedObject = TestHelper::createEmptyObject();
        $this->assertTrue($savedObject->getId() > 0);

        $savedObject->setParentId($savedObject->getId());
        $savedObject->save();
    }

    /**
     * Verifies that an object cannot be moved into one of its own children, which
     * would create a circular parent/child reference in the tree.
     */
    public function testParentCannotBeOwnDescendant(): void
    {
        $parent = TestHelper::createEmptyObject();
        $child = TestHelper::createEmptyObject('', false);
        $child->setParentId($parent->getId());
        $child->save();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot set parent, because the new parent is one of its own children, which would create a circular reference in the tree.');

        $parent->setParentId($child->getId());
        $parent->save();
    }

    /**
     * Verifies that an object cannot be moved into a grandchild several levels down,
     * which would create a circular parent/child reference in the tree.
     */
    public function testParentCannotBeOwnGrandchild(): void
    {
        $grandParent = TestHelper::createEmptyObject();
        $parent = TestHelper::createEmptyObject('', false);
        $parent->setParentId($grandParent->getId());
        $parent->save();
        $child = TestHelper::createEmptyObject('', false);
        $child->setParentId($parent->getId());
        $child->save();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot set parent, because the new parent is one of its own children, which would create a circular reference in the tree.');

        $grandParent->setParentId($child->getId());
        $grandParent->save();
    }

    /**
     * Verifies that the locking variant of getCurrentFullPath() used to re-check for
     * circular parent references inside the save transaction returns the same path.
     */
    public function testGetCurrentFullPathForUpdateMatchesGetCurrentFullPath(): void
    {
        $object = TestHelper::createEmptyObject();

        $this->assertSame($object->getDao()->getCurrentFullPath(), $object->getDao()->getCurrentFullPathForUpdate());
    }

    /**
     * Verifies that object PHP API version note is saved
     */
    public function testSavingVersionNotes(): void
    {
        $versionNote = ['versionNote' => 'a new version of this object'];
        $this->testObject = TestHelper::createEmptyObject();
        $this->testObject->save($versionNote);
        $this->assertEquals($this->testObject->getLatestVersion(null, true)->getNote(), $versionNote['versionNote']);
    }

    /**
     * Parent ID of a new object cannot be 0
     */
    public function testParentIs0(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ParentID is mandatory and can´t be null. If you want to add the element as a child to the tree´s root node, consider setting ParentID to 1.');
        $savedObject = TestHelper::createEmptyObject('', false);
        $this->assertNull($savedObject->getId());

        $savedObject->setParentId(0);
        $savedObject->save();
    }

    /**
     * Parent ID of a new object cannot be null
     */
    public function testParentIsNull(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ParentID is mandatory and can´t be null. If you want to add the element as a child to the tree´s root node, consider setting ParentID to 1.');
        $savedObject = TestHelper::createEmptyObject('', false);
        $this->assertNull($savedObject->getId());

        $savedObject->setParentId(null);
        $savedObject->save();
    }

    /**
     * Parent ID must resolve to an existing element
     *
     * @group notfound
     */
    public function testParentNotFound(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ParentID not found.');
        $savedObject = TestHelper::createEmptyObject('', false);
        $this->assertEquals(null, $savedObject->getId());

        $savedObject->setParentId(999999);
        $savedObject->save();
    }

    /**
     * Verifies that children result should be cached based on parameters provided.
     *
     */
    public function testCacheUnpublishedChildren(): void
    {
        // create parent
        $parent = TestHelper::createEmptyObject();

        // create first child
        $firstChild = TestHelper::createEmptyObject('child-', false, false);
        $firstChild->setParentId($parent->getId());
        $firstChild->save();

        //without unpublished flag
        $child = $parent->getChildren()->load();
        $this->assertEquals(0, count($child), 'Expected no child');

        $hasChild = $parent->hasChildren();
        $this->assertFalse($hasChild, 'hasChild property should be false');

        //with unpublished flag
        $child = $parent->getChildren([], true)->load();
        $this->assertEquals(1, count($child), 'Expected 1 child');

        $hasChild = $parent->hasChildren([], true);
        $this->assertTrue($hasChild, 'hasChild property should be true');
    }

    /**
     * Verifies that siblings result should be cached based on parameters provided.
     *
     */
    public function testCacheUnpublishedSiblings(): void
    {
        // create parent
        $parent = TestHelper::createEmptyObject();

        // create first child
        $firstChild = TestHelper::createEmptyObject('child-', false);
        $firstChild->setParentId($parent->getId());
        $firstChild->save();

        // create first child
        $secondChild = TestHelper::createEmptyObject('child-', false, false);
        $secondChild->setParentId($parent->getId());
        $secondChild->save();

        //without unpublished flag
        $sibling = $firstChild->getSiblings()->load();
        $this->assertEquals(0, count($sibling), 'Expected no sibling');

        $hasSibling = $firstChild->hasSiblings();
        $this->assertFalse($hasSibling, 'hasSiblings property should be false');

        //with unpublished flag
        $sibling = $firstChild->getSiblings([], true)->load();
        $this->assertEquals(1, count($sibling), 'Expected 1 sibling');

        $hasSibling = $firstChild->hasSiblings([], true);
        $this->assertTrue($hasSibling, 'hasSiblings property should be true');
    }

    /**
     * Verifies that an object can be saved with custom user modification id.
     *
     */
    public function testCustomUserModification(): void
    {
        $userId = 101;
        $object = TestHelper::createEmptyObject();

        //custom user modification
        $object->setUserModification($userId);
        $object->save();
        $this->assertEquals($userId, $object->getUserModification(), 'Expected custom user modification id');

        //auto generated user modification
        $object = DataObject::getById($object->getId(), ['force' => true]);
        $object->save();
        $this->assertEquals(0, $object->getUserModification(), 'Expected auto assigned user modification id');
    }

    /**
     * Verifies that an object can be saved with custom modification date.
     *
     */
    public function testCustomModificationDate(): void
    {
        $customDateTime = new \Carbon\Carbon();
        $customDateTime = $customDateTime->subHour();

        $object = TestHelper::createEmptyObject();

        //custom modification date
        $object->setModificationDate($customDateTime->getTimestamp());
        $object->save();
        $this->assertEquals($customDateTime->getTimestamp(), $object->getModificationDate(), 'Expected custom modification date');

        //auto generated modification date
        $currentTime = time();
        $object = DataObject::getById($object->getId(), ['force' => true]);
        $object->save();
        $this->assertGreaterThanOrEqual($currentTime, $object->getModificationDate(), 'Expected auto assigned modification date');
    }

    /**
     * Verifies that when an object gets saved default values of fields get saved to the version
     */
    public function testDefaultValueSavedToVersion(): void
    {
        $object = TestHelper::createEmptyObject();
        $object->save();

        $versions = $object->getVersions();
        $latestVersion = end($versions);

        $this->assertEquals('default', $latestVersion->getData()->getInputWithDefault(), 'Expected default value saved to version');
    }

    /**
     * Verifies a newly published object gets the default values of mandatory fields
     */
    public function testDefaultValueAndMandatorySavedToVersion(): void
    {
        $object = TestHelper::createEmptyObject('', false, true);
        $object->setOmitMandatoryCheck(false);
        $object->save();

        $versions = $object->getVersions();
        $latestVersion = end($versions);

        $this->assertEquals('default', $latestVersion->getData()->getMandatoryInputWithDefault(), 'Expected default value saved to version');
    }

    /**
     * Regression test for PEES-1279: a mandatory numeric field defaulting to
     * 0, and a mandatory checkbox field defaulting to false, must not block
     * publishing a brand-new object, and their default must be persisted.
     */
    public function testMandatoryZeroAndFalseDefaultsSavedToVersion(): void
    {
        $object = TestHelper::createEmptyObject('', false, true);
        $object->setOmitMandatoryCheck(false);
        $object->save();

        $versions = $object->getVersions();
        $latestVersion = end($versions);
        $data = $latestVersion->getData();

        $this->assertSame(0, $data->getMandatoryNumericWithZeroDefault(), 'Expected numeric default of 0 saved to version');
        $this->assertFalse($data->getMandatoryCheckboxWithFalseDefault(), 'Expected checkbox default of false saved to version');
    }

    /**
     * Verifies that when an object gets cloned, the fields get copied properly
     */
    public function testCloning(): void
    {
        $object = TestHelper::createEmptyObject('', false);
        $clone = Service::cloneMe($object);

        $object->setId(123);

        $this->assertEquals(null, $clone->getId(), 'Setting ID on original object should have no impact on the cloned object');

        $otherClone = clone $object;
        $this->assertEquals(123, $otherClone->getId(), 'Shallow clone should copy the fields');
    }

    /**
     * Verifies that loading only Concrete object from Concrete::getById().
     */
    public function testConcreteLoading(): void
    {
        $concreteObject = TestHelper::createEmptyObject();
        $loadedConcrete = DataObject\Concrete::getById($concreteObject->getId(), ['force' => true]);

        $this->assertIsObject($loadedConcrete, 'Loaded Concrete should be an object.');

        $nonConcreteObject = TestHelper::createObjectFolder();
        $loadedNonConcrete = DataObject\Concrete::getById($nonConcreteObject->getId(), ['force' => true]);

        $this->assertNull($loadedNonConcrete, 'Loaded Concrete should be null.');
    }

    /**
     * Values should be stored as they are passed. E.g. passing '' (empty string) to a setter function should be stored as such in the database.
     * Passing null to a setter function should be stored as null in the database.
     */
    public function testEmptyValuesAsNullApi(): void
    {
        $db = Db::get();

        $object = TestHelper::createEmptyObject();
        $object->setInput('InputValue');
        $object->setTextarea('TextareaValue');
        $object->setWysiwyg('WysiwygValue');
        $object->setPassword('PasswordValue');
        $iqv = new \Pimcore\Model\DataObject\Data\InputQuantityValue('1', Unit::getByAbbreviation('km')->getId());
        $object->setInputQuantityValue($iqv);
        $object->save();

        //check if empty strings are stored as empty strings in the database
        $object->setInput('');
        $object->setTextarea('');
        $object->setWysiwyg('');
        $object->setPassword('');
        $iqv = new \Pimcore\Model\DataObject\Data\InputQuantityValue('', '');
        $object->setInputQuantityValue($iqv);
        $object->save();

        $result = $db->fetchAllAssociative('select * from object_store_' . $object->getClassId() . ' where oo_id=' .  $object->getId());
        $this->assertTrue($result[0]['input'] === '');
        $this->assertTrue($result[0]['textarea'] === '');
        $this->assertTrue($result[0]['wysiwyg'] === '');
        $this->assertNull($result[0]['password']);
        $this->assertTrue($result[0]['inputQuantityValue__value'] === '');

        //check if null values are stored as null in the database
        $object->setInput(null);
        $object->setTextarea(null);
        $object->setWysiwyg(null);
        $object->setPassword(null);
        $object->setInputQuantityValue(null);
        $object->save();

        $result = $db->fetchAllAssociative('select * from object_store_' . $object->getClassId() . ' where oo_id=' .  $object->getId());
        $this->assertNull($result[0]['input']);
        $this->assertNull($result[0]['textarea']);
        $this->assertNull($result[0]['wysiwyg']);
        $this->assertNull($result[0]['password']);
        $this->assertNull($result[0]['inputQuantityValue__value']);
    }

    /**
     * In contrast to the api calls, empty strings and null values should be stored as null if the save was triggered from the backend ui.
     */
    public function testEmptyValuesAsNullBackend(): void
    {
        $object = TestHelper::createEmptyObject();

        //check empty strings
        $dataType = new \Pimcore\Model\DataObject\ClassDefinition\Data\Input();
        $value = $dataType->getDataFromEditmode('', $object);
        $this->assertNull($value);

        $dataType = new \Pimcore\Model\DataObject\ClassDefinition\Data\Textarea();
        $value = $dataType->getDataFromEditmode('', $object);
        $this->assertNull($value);

        $dataType = new \Pimcore\Model\DataObject\ClassDefinition\Data\Wysiwyg();
        $value = $dataType->getDataFromEditmode('', $object);
        $this->assertNull($value);

        $dataType = new \Pimcore\Model\DataObject\ClassDefinition\Data\Password();
        $value = $dataType->getDataFromEditmode('', $object);
        $this->assertNull($value);

        $dataType = new \Pimcore\Model\DataObject\ClassDefinition\Data\InputQuantityValue();
        $iqv = ['value' => '', 'unit' => ''];
        $value = $dataType->getDataFromEditmode($iqv, $object);
        $this->assertNull($value);

        //check null values
        $dataType = new \Pimcore\Model\DataObject\ClassDefinition\Data\Input();
        $value = $dataType->getDataFromEditmode(null, $object);
        $this->assertNull($value);

        $dataType = new \Pimcore\Model\DataObject\ClassDefinition\Data\Textarea();
        $value = $dataType->getDataFromEditmode(null, $object);
        $this->assertNull($value);

        $dataType = new \Pimcore\Model\DataObject\ClassDefinition\Data\Wysiwyg();
        $value = $dataType->getDataFromEditmode(null, $object);
        $this->assertNull($value);

        $dataType = new \Pimcore\Model\DataObject\ClassDefinition\Data\Password();
        $value = $dataType->getDataFromEditmode(null, $object);
        $this->assertNull($value);

        $dataType = new \Pimcore\Model\DataObject\ClassDefinition\Data\InputQuantityValue();
        $iqv = ['value' => null, 'unit' => null];
        $value = $dataType->getDataFromEditmode($iqv, $object);
        $this->assertNull($value);
    }

    public function testSanitization(): void
    {
        $db = Db::get();

        $object = TestHelper::createEmptyObject();
        $object->setWysiwyg('!@#$%^abc\'"<script>console.log("ops");</script> 测试&lt; edf &gt; "');
        $object->save();

        //reload from db
        $object = DataObject::getById($object->getId(), ['force' => true]);

        $this->assertEquals('!@#$%^abc\'" 测试< edf > "', html_entity_decode($object->getWysiwyg()), 'Asseting setter/getter value is sanitized');

        $dbQueryValue = $db->fetchOne(
            sprintf(
                'SELECT `wysiwyg` FROM object_query_%s WHERE oo_id = %d',
                $object->getClassName(),
                $object->getId()
            )
        );
        $this->assertEquals('!@#$%^abc\'" 测试< edf > "', html_entity_decode($dbQueryValue), 'Asserting object_query table value is persisted as sanitized');
    }

    public function testInputCheckValidate(): void
    {
        $this->expectException(ValidationException::class);

        $targetObject = TestHelper::createEmptyObject();
        $randomText = TestHelper::generateRandomString(500);

        $targetObject->setInput($randomText);
        $targetObject->save();
    }

    /**
     * Regression test: DataObject\AbstractObject::getByPath() must resolve an object whose key
     * is stored precomposed (NFC) when the caller supplies a decomposed (NFD) lookup path -
     * e.g. a browser's webkitdirectory/File System Access API on macOS - by falling back to
     * the NFC-normalized path once the exact-path lookup misses. DataObject's getByPath() is
     * an independently implemented call site of the shared Element\Service fallback, so it
     * needs its own coverage rather than relying on the Asset regression tests alone.
     *
     * @see \Pimcore\Model\Element\Service::getByPathWithNfcFallback()
     */
    public function testGetByPathFallsBackToNfcForNfdLookupPath(): void
    {
        $nfcKey = Normalizer::normalize('nfc-café-' . uniqid(), Normalizer::FORM_C);

        $folder = new DataObject\Folder();
        $folder->setParentId(1);
        $folder->setKey($nfcKey);
        $folder->save();

        $nfdLookupPath = Normalizer::normalize($folder->getFullPath(), Normalizer::FORM_D);
        $this->assertNotSame(
            $folder->getFullPath(),
            $nfdLookupPath,
            'Test fixture setup issue: NFD and NFC forms should differ in bytes.'
        );

        $found = DataObject::getByPath($nfdLookupPath, ['force' => true]);

        $this->assertInstanceOf(DataObject::class, $found);
        $this->assertSame($folder->getId(), $found->getId());
    }

    /**
     * Regression test: DataObject\AbstractObject::getByPath() must still resolve an object
     * whose key is stored in decomposed (NFD) Unicode form - e.g. created before object keys
     * were normalized to NFC on write - via an exact-path lookup using that very same NFD
     * path.
     *
     * The model API can no longer persist such a key directly: AbstractObject::save() rejects
     * it via Service::isValidKey(), which always normalizes to NFC before comparing (#19242).
     * So the legacy row is created via a valid NFC key first, then rewritten to NFD directly
     * in the database - bypassing model validation - to simulate a pre-#19242 row.
     *
     * @see \Pimcore\Model\Element\Service::correctPath()
     */
    public function testGetByPathResolvesLegacyNfdStoredKeyByExactMatch(): void
    {
        $nfcKey = Normalizer::normalize('nfd-café-' . uniqid(), Normalizer::FORM_C);
        $nfdKey = Normalizer::normalize($nfcKey, Normalizer::FORM_D);
        $this->assertNotSame(
            $nfcKey,
            $nfdKey,
            'Test fixture setup issue: NFD and NFC forms should differ in bytes.'
        );

        $folder = new DataObject\Folder();
        $folder->setParentId(1);
        $folder->setKey($nfcKey);
        $folder->save();

        $lookupPath = $folder->getRealPath() . $nfdKey;

        $db = Db::get();
        $db->update('objects', DbHelper::quoteDataIdentifiers($db, ['key' => $nfdKey]), ['id' => $folder->getId()]);

        $found = DataObject::getByPath($lookupPath, ['force' => true]);

        $this->assertInstanceOf(DataObject::class, $found);
        $this->assertSame($folder->getId(), $found->getId());
    }

    /**
     * Regression test: DataObject\Service::pathExists() must agree with
     * DataObject\AbstractObject::getByPath() on whether a path resolves. Without routing
     * pathExists() through the same NFC fallback, a caller could see pathExists() return false
     * for the very same NFD lookup path that getByPath() successfully resolves, and
     * incorrectly treat an existing object as absent.
     *
     * @see \Pimcore\Model\Element\Service::getByPathWithNfcFallback()
     */
    public function testPathExistsAgreesWithGetByPathForNfdLookupPath(): void
    {
        $nfcKey = Normalizer::normalize('nfc-café-' . uniqid(), Normalizer::FORM_C);

        $folder = new DataObject\Folder();
        $folder->setParentId(1);
        $folder->setKey($nfcKey);
        $folder->save();

        $nfdLookupPath = Normalizer::normalize($folder->getFullPath(), Normalizer::FORM_D);
        $this->assertNotSame(
            $folder->getFullPath(),
            $nfdLookupPath,
            'Test fixture setup issue: NFD and NFC forms should differ in bytes.'
        );

        $this->assertTrue(
            DataObjectService::pathExists($nfdLookupPath),
            'pathExists() must return true for the same NFD path that getByPath() resolves.'
        );
    }
}
