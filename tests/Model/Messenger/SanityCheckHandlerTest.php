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

namespace Pimcore\Tests\Model\Messenger;

use Pimcore\Db;
use Pimcore\Messenger\Handler\SanityCheckHandler;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;
use ReflectionMethod;

/**
 * A sanity check runs on an element instance loaded before the check; when the element (or its
 * parent) was deleted in the meantime, saving that instance would re-insert it as an orphan.
 *
 * @group model.messenger
 */
class SanityCheckHandlerTest extends ModelTestCase
{
    public function testDeletedElementIsNotReInserted(): void
    {
        $object = TestHelper::createEmptyObject();
        $id = $object->getId();

        // the instance the handler works on, loaded while the element still exists
        $stale = Concrete::getById($id, ['force' => true]);
        $this->assertInstanceOf(Concrete::class, $stale);

        $object->delete();
        $this->assertSame(0, $this->countRows($id));

        $this->performSanityCheck($stale);

        $this->assertSame(0, $this->countRows($id), 'the sanity check must not re-insert a deleted element');
        $this->assertNull(DataObject::getById($id, ['force' => true]));
    }

    public function testElementWhoseParentWasDeletedIsNotReInserted(): void
    {
        $folder = TestHelper::createObjectFolder();
        $object = TestHelper::createEmptyObject();
        $object->setParentId($folder->getId());
        $object->save();
        $id = $object->getId();

        $stale = Concrete::getById($id, ['force' => true]);
        $this->assertInstanceOf(Concrete::class, $stale);

        // deleting the folder deletes the object with it, as a bulk deletion does
        $folder->delete();
        $this->assertSame(0, $this->countRows($id));

        $this->performSanityCheck($stale);

        $this->assertSame(0, $this->countRows($id));
    }

    public function testExistingElementIsSaved(): void
    {
        $object = TestHelper::createEmptyObject();
        $id = $object->getId();
        $versionCount = $object->getVersionCount();

        $this->performSanityCheck(Concrete::getById($id, ['force' => true]));

        $saved = Concrete::getById($id, ['force' => true]);
        $this->assertInstanceOf(Concrete::class, $saved);
        $this->assertGreaterThan($versionCount, $saved->getVersionCount());
        $this->assertSame(0, $saved->getUserModification());
    }

    private function performSanityCheck(ElementInterface $element): void
    {
        $method = new ReflectionMethod(SanityCheckHandler::class, 'performSanityCheck');
        $method->invoke(new SanityCheckHandler(), $element);
    }

    private function countRows(int $id): int
    {
        return (int) Db::get()->fetchOne('SELECT COUNT(*) FROM objects WHERE id = ?', [$id]);
    }
}
