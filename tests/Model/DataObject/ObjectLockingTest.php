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

use Doctrine\DBAL\Connection;
use Exception;
use InvalidArgumentException;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Class ObjectLockingTest
 *
 * @package Pimcore\Tests\Model\DataObject
 *
 * @group model.dataobject.object
 */
final class ObjectLockingTest extends ModelTestCase
{
    protected function tearDown(): void
    {
        AbstractObject::enableLocking();
        parent::tearDown();
    }

    public function testLockingIsEnabledByDefault(): void
    {
        $this->assertFalse(AbstractObject::isLockingDisabled(), 'Hydration locking must be enabled by default.');
    }

    public function testDisableLockingAccessors(): void
    {
        AbstractObject::disableLocking();
        $this->assertTrue(AbstractObject::isLockingDisabled());

        AbstractObject::enableLocking();
        $this->assertFalse(AbstractObject::isLockingDisabled());

        AbstractObject::setDisableLocking(true);
        $this->assertTrue(AbstractObject::isLockingDisabled());

        AbstractObject::setDisableLocking(false);
        $this->assertFalse(AbstractObject::isLockingDisabled());
    }

    public function testGetByIdWithLockParamRestoresLockingState(): void
    {
        $object = TestHelper::createEmptyObject();

        $loaded = Concrete::getById($object->getId(), ['force' => true, 'lock' => false]);
        $this->assertInstanceOf(Concrete::class, $loaded);
        $this->assertSame($object->getId(), $loaded->getId());
        $this->assertFalse(
            AbstractObject::isLockingDisabled(),
            'Locking state must be restored after getById() with lock param.'
        );

        AbstractObject::disableLocking();
        $loaded = Concrete::getById($object->getId(), ['force' => true, 'lock' => false]);
        $this->assertInstanceOf(Concrete::class, $loaded);
        $this->assertTrue(
            AbstractObject::isLockingDisabled(),
            'Previously disabled locking must stay disabled after getById() with lock param.'
        );
    }

    public function testGetByPathSupportsLockParam(): void
    {
        $object = TestHelper::createEmptyObject();

        $loaded = DataObject::getByPath($object->getFullPath(), ['force' => true, 'lock' => false]);
        $this->assertInstanceOf(Concrete::class, $loaded);
        $this->assertSame($object->getId(), $loaded->getId());
        $this->assertFalse(
            AbstractObject::isLockingDisabled(),
            'Locking state must be restored after getByPath() with lock param.'
        );
    }

    public function testGetByIdRejectsNonBooleanLockParam(): void
    {
        $object = TestHelper::createEmptyObject();

        $this->expectException(InvalidArgumentException::class);
        Concrete::getById($object->getId(), ['force' => true, 'lock' => 'false']);
    }

    public function testGetByPathRejectsNonBooleanLockParam(): void
    {
        $object = TestHelper::createEmptyObject();

        $this->expectException(InvalidArgumentException::class);
        DataObject::getByPath($object->getFullPath(), ['lock' => 1]);
    }

    public function testGetByIdRejectsNullLockParam(): void
    {
        $object = TestHelper::createEmptyObject();

        $this->expectException(InvalidArgumentException::class);
        Concrete::getById($object->getId(), ['force' => true, 'lock' => null]);
    }

    public function testGetDataAppendsForUpdateOnlyWhenLockingEnabled(): void
    {
        $object = TestHelper::createEmptyObject();
        $dao = $object->getDao();
        $originalDb = $dao->db;

        $capturedSql = [];
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAssociative')->willReturnCallback(
            function (string $sql, array $params = [], array $types = []) use (&$capturedSql): array|false {
                $capturedSql[] = $sql;

                return false;
            }
        );

        $dao->db = $connection;

        try {
            $dao->getData();
            AbstractObject::disableLocking();
            $dao->getData();
        } finally {
            $dao->db = $originalDb;
            AbstractObject::enableLocking();
        }

        $this->assertCount(2, $capturedSql);
        $this->assertStringContainsString('FOR UPDATE', $capturedSql[0], 'Hydration query must lock by default.');
        $this->assertStringNotContainsString(
            'FOR UPDATE',
            $capturedSql[1],
            'Hydration query must not lock while locking is disabled.'
        );
    }

    public function testLockingStateIsRestoredWhenHydrationFails(): void
    {
        $object = TestHelper::createEmptyObject();
        $id = $object->getId();
        $originalClassId = $object->getClassId();
        $db = Db::get();

        // point the object to a non-existing class, so hydration fails while loading the object data
        $db->executeStatement('UPDATE objects SET classId = ? WHERE id = ?', ['brokenLockingTest', $id]);

        $hydrationFailed = false;

        try {
            Concrete::getById($id, ['force' => true, 'lock' => false]);
        } catch (Exception) {
            $hydrationFailed = true;
        } finally {
            $db->executeStatement('UPDATE objects SET classId = ? WHERE id = ?', [$originalClassId, $id]);
            RuntimeCache::clear();
        }

        $this->assertTrue($hydrationFailed, 'Hydration was expected to fail with a broken class id.');
        $this->assertFalse(
            AbstractObject::isLockingDisabled(),
            'Locking state must be restored when hydration fails.'
        );
    }
}
