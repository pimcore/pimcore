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

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Pimcore;
use Pimcore\Db;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\HydrationLock;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\Exception\StaleDataException;
use Pimcore\Model\Version;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use Pimcore\Tool;

/**
 * Covers the database-maintained change token on the objects row and the save-time stale check.
 *
 * @group model.dataobject.object
 */
final class ChangeTokenTest extends ModelTestCase
{
    private ?TestHandler $logHandler = null;

    protected function tearDown(): void
    {
        HydrationLock::reset();
        Version::enable();
        if ($this->logHandler !== null) {
            $this->getPimcoreLogger()->popHandler();
            $this->logHandler = null;
        }
        parent::tearDown();
    }

    public function testTokenIsSetOnCreateAndRenewedOnEverySave(): void
    {
        Version::disable();
        $object = TestHelper::createEmptyObject();
        $this->assertNotNull($object->__getChangeToken(), 'A created object must carry the token of its row.');
        $this->assertSame($this->dbToken($object->getId()), $object->__getChangeToken());

        // a store-only change in the same second leaves every other objects column unchanged,
        // so only the explicit renewal of the save can change the token
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $object->setInput('settle-' . $attempt);
            $object->save();
            $rowBefore = $this->dbRowWithoutToken($object->getId());
            $tokenBefore = $object->__getChangeToken();

            $object->setInput('changed-' . $attempt);
            $object->save();

            // retry when a second boundary changed the modification date in between
            if ($rowBefore === $this->dbRowWithoutToken($object->getId())) {
                break;
            }
        }

        $this->assertSame($rowBefore, $this->dbRowWithoutToken($object->getId()), 'Precondition: objects row unchanged.');
        $this->assertNotSame($tokenBefore, $object->__getChangeToken());
        $this->assertSame($this->dbToken($object->getId()), $object->__getChangeToken());
    }

    public function testStaleSaveThrowsForOptedInClass(): void
    {
        HydrationLock::disable(Unittest::class);
        $id = TestHelper::createEmptyObject()->getId();
        $stale = $this->load($id);
        $current = $this->load($id);
        $current->setInput('current');
        $current->save();

        $stale->setInput('stale');

        try {
            $stale->save();
            $this->fail('Saving an outdated copy of an opted-in class must throw.');
        } catch (StaleDataException) {
        }

        $this->assertSame('current', $this->load($id)->getInput());
    }

    public function testStaleSaveOnDefaultPathSavesAndWarns(): void
    {
        $log = $this->captureLog();
        $id = TestHelper::createEmptyObject()->getId();
        $stale = $this->load($id);
        $current = $this->load($id);
        $current->setInput('current');
        $current->save();

        $stale->setInput('stale');
        $stale->save();

        $this->assertSame('stale', $this->load($id)->getInput(), 'The default path must keep today\'s behavior.');
        $this->assertTrue($log->hasWarningThatContains('outdated data'));
    }

    public function testSameInstanceCanBeSavedRepeatedly(): void
    {
        HydrationLock::disable(Unittest::class);
        $object = $this->load(TestHelper::createEmptyObject()->getId());

        foreach (['a', 'b', 'c'] as $value) {
            $object->setInput($value);
            $object->save();
        }

        $this->assertSame('c', $this->load($object->getId())->getInput());
    }

    public function testDirectSqlWriteInvalidatesLoadedCopy(): void
    {
        HydrationLock::disable(Unittest::class);
        $id = TestHelper::createEmptyObject()->getId();
        $stale = $this->load($id);

        // saveIndex() writes the objects row with plain SQL, without a save of the object
        $this->load($id)->saveIndex(5);

        $this->expectException(StaleDataException::class);
        $stale->save();
    }

    public function testParentRenameInvalidatesLoadedChild(): void
    {
        HydrationLock::disable(Unittest::class);
        $parent = TestHelper::createEmptyObject();
        $child = TestHelper::createEmptyObject('', false);
        $child->setParentId($parent->getId());
        $child->save();
        $stale = $this->load($child->getId());

        $parent = $this->load($parent->getId());
        $parent->setKey($parent->getKey() . '-renamed');
        $parent->save();

        $this->expectException(StaleDataException::class);
        $stale->save();
    }

    public function testTokenColumnIsMaintainedByTheDatabase(): void
    {
        $id = TestHelper::createEmptyObject()->getId();
        $db = Db::get();
        $before = $this->dbToken($id);
        usleep(1000);

        // fails if the ON UPDATE CURRENT_TIMESTAMP(6) clause of the column is ever dropped
        $db->executeStatement('UPDATE objects SET userModification = 4242 WHERE id = ?', [$id]);

        $this->assertNotSame($before, $this->dbToken($id));
    }

    public function testObjectWithoutTokenIsNotChecked(): void
    {
        HydrationLock::disable(Unittest::class);
        $id = TestHelper::createEmptyObject()->getId();
        $withoutToken = $this->load($id);
        $current = $this->load($id);
        $current->setInput('current');
        $current->save();

        // e.g. an object cached before the column existed
        $withoutToken->__setChangeToken(null);
        $withoutToken->setInput('unchecked');
        $withoutToken->save();

        $this->assertSame('unchecked', $this->load($id)->getInput());
    }

    public function testVersionDumpDoesNotCarryTheToken(): void
    {
        HydrationLock::disable(Unittest::class);
        $object = $this->load(TestHelper::createEmptyObject()->getId());
        $object->setInput('v1');
        $object->save();
        $object->setInput('v2');
        $object->save();

        $restored = null;
        foreach ($object->getVersions() as $version) {
            $data = $version->loadData();
            if ($data instanceof Unittest && $data->getInput() === 'v1') {
                $restored = $data;
            }
        }

        $this->assertInstanceOf(Unittest::class, $restored);
        $this->assertNull($restored->__getChangeToken());

        // publishing an older version must not be treated as a stale save
        $restored->save();
        $this->assertSame('v1', $this->load($object->getId())->getInput());
    }

    public function testDeletedRowIsNotRecreatedForOptedInClass(): void
    {
        HydrationLock::disable(Unittest::class);
        $id = TestHelper::createEmptyObject()->getId();
        $stale = $this->load($id);
        $this->load($id)->delete();

        try {
            $stale->save();
            $this->fail('Saving a copy of an object deleted in the meantime must throw for an opted-in class.');
        } catch (StaleDataException) {
        }

        $this->assertFalse(Db::get()->fetchOne('SELECT id FROM objects WHERE id = ?', [$id]));
    }

    public function testCopyContentsIsNotAStaleSave(): void
    {
        HydrationLock::disable(Unittest::class);
        $source = $this->load(TestHelper::createEmptyObject()->getId());
        $source->setInput('copied');
        $source->save();
        $target = $this->load(TestHelper::createEmptyObject()->getId());

        // the copy is a clone of the source saved under the id of the target
        (new DataObject\Service())->copyContents($target, $source);

        $this->assertSame('copied', $this->load($target->getId())->getInput());
    }

    public function testFieldNamedChangeTokenDoesNotCollide(): void
    {
        $class = $this->createClassWithChangeTokenField();

        try {
            $fqcn = 'Pimcore\\Model\\DataObject\\' . $class->getName();
            $object = new $fqcn();
            $object->setKey(uniqid('change-token-field-'));
            $object->setParentId(1);
            $object->setPublished(true);
            $object->setChangeToken('field value');
            $object->setTitle('title', Tool::getDefaultLanguage());
            $object->save();

            $loaded = DataObject::getById($object->getId(), ['force' => true]);
            $this->assertSame('field value', $loaded->getChangeToken());
            $this->assertSame($this->dbToken($object->getId()), $loaded->__getChangeToken());

            // listings query the object view, localized listings the localized view: both need the field, not the token
            $listingClass = $fqcn . '\\Listing';
            $listing = new $listingClass();
            $listing->setCondition('changeToken = ?', ['field value']);
            $this->assertSame([$object->getId()], $listing->loadIdList());

            $localizedListing = new $listingClass();
            $localizedListing->setLocale(Tool::getDefaultLanguage());
            $localizedListing->setCondition('changeToken = ?', ['field value']);
            $this->assertSame([$object->getId()], $localizedListing->loadIdList());
        } finally {
            $class->delete();
        }
    }

    public function testListingViewsDoNotReferenceTheToken(): void
    {
        $class = $this->createClassWithChangeTokenField();

        try {
            // the localized views are created with the first localized data
            $fqcn = 'Pimcore\\Model\\DataObject\\' . $class->getName();
            $object = new $fqcn();
            $object->setKey(uniqid('change-token-view-'));
            $object->setParentId(1);
            $object->setTitle('title', Tool::getDefaultLanguage());
            $object->save();

            // a view referencing the column would break once the migration is rolled back and the column is dropped
            $definitions = Db::get()->fetchFirstColumn(
                'SELECT VIEW_DEFINITION FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (?, ?)',
                ['object_' . $class->getId(), 'object_localized_' . $class->getId() . '_' . Tool::getDefaultLanguage()]
            );

            $this->assertCount(2, $definitions);
            foreach ($definitions as $definition) {
                $this->assertStringNotContainsString('`objects`.`changeToken`', $definition);
            }
        } finally {
            $class->delete();
        }
    }

    private function createClassWithChangeTokenField(): DataObject\ClassDefinition
    {
        $field = new DataObject\ClassDefinition\Data\Input();
        $field->setName('changeToken');
        $field->setTitle('Change token field');
        $title = new DataObject\ClassDefinition\Data\Input();
        $title->setName('title');
        $title->setTitle('Title');
        $localizedFields = new DataObject\ClassDefinition\Data\Localizedfields();
        $localizedFields->setName('localizedfields');
        $localizedFields->addChild($title);
        $panel = new DataObject\ClassDefinition\Layout\Panel();
        $panel->setName('pimcore_root');
        $panel->addChild($field);
        $panel->addChild($localizedFields);

        $class = new DataObject\ClassDefinition();
        $class->setName('ChangeTokenFieldTest');
        $class->setId('ctft');
        $class->setLayoutDefinitions($panel);
        $class->save();

        return $class;
    }

    private function load(int $id): Unittest
    {
        $object = DataObject::getById($id, ['force' => true]);
        $this->assertInstanceOf(Unittest::class, $object);

        return $object;
    }

    private function dbToken(int $id): string
    {
        return (string) Db::get()->fetchOne('SELECT changeToken FROM objects WHERE id = ?', [$id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dbRowWithoutToken(int $id): array
    {
        $row = Db::get()->fetchAssociative('SELECT * FROM objects WHERE id = ?', [$id]);
        $this->assertIsArray($row);
        unset($row['changeToken']);

        return $row;
    }

    private function captureLog(): TestHandler
    {
        $this->logHandler = new TestHandler();
        $this->getPimcoreLogger()->pushHandler($this->logHandler);

        return $this->logHandler;
    }

    private function getPimcoreLogger(): Logger
    {
        $logger = Pimcore::getContainer()->get('monolog.logger.pimcore');
        $this->assertInstanceOf(Logger::class, $logger);

        return $logger;
    }
}
