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

use InvalidArgumentException;
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
class ObjectLockingTest extends ModelTestCase
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
}
