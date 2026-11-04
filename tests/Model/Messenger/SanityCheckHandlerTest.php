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
use Pimcore\Model\Element\Service;
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

    public function testElementWhoseParentRowIsGoneIsNotSaved(): void
    {
        $folder = TestHelper::createObjectFolder();
        $object = TestHelper::createEmptyObject();
        $object->setParentId($folder->getId());
        $object->save();
        $id = $object->getId();
        $versionCount = $object->getVersionCount();

        $stale = Concrete::getById($id, ['force' => true]);
        $this->assertInstanceOf(Concrete::class, $stale);

        // only the parent's row disappears (the deleting process has not reached the child yet)
        Db::get()->delete('objects', ['id' => $folder->getId()]);
        $this->assertSame(1, $this->countRows($id));

        try {
            $this->performSanityCheck($stale);

            $this->assertSame(1, $this->countRows($id));
            $this->assertSame($versionCount, (int) Db::get()->fetchOne('SELECT versionCount FROM objects WHERE id = ?', [$id]));
            $this->assertSame(0, (int) Db::get()->fetchOne('SELECT COUNT(*) FROM versions WHERE ctype = ? AND cid = ? AND note = ?', ['object', $id, 'Sanity Check']));
        } finally {
            // do not leave the orphan behind for the next test
            Db::get()->delete('objects', ['id' => $id]);
            Db::get()->delete('versions', ['ctype' => 'object', 'cid' => $id]);
        }
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

    public function testDeletedAssetIsNotReInserted(): void
    {
        $this->assertDeletedElementIsNotReInserted(TestHelper::createImageAsset(), 'assets');
    }

    public function testDeletedPageIsNotReInserted(): void
    {
        $this->assertDeletedElementIsNotReInserted(TestHelper::createEmptyDocumentPage(), 'documents');
    }

    public function testAssetWhoseParentRowIsGoneIsNotSaved(): void
    {
        $folder = TestHelper::createAssetFolder();
        $asset = TestHelper::createImageAsset('', null, false);
        $asset->setParentId($folder->getId());
        $asset->save();

        $this->assertElementWhoseParentRowIsGoneIsNotSaved($asset, $folder->getId(), 'assets');
    }

    public function testPageWhoseParentRowIsGoneIsNotSaved(): void
    {
        $folder = TestHelper::createDocumentFolder();
        $page = TestHelper::createEmptyDocumentPage('', false);
        $page->setParentId($folder->getId());
        $page->save();

        $this->assertElementWhoseParentRowIsGoneIsNotSaved($page, $folder->getId(), 'documents');
    }

    public function testExistingAssetIsSaved(): void
    {
        $this->assertExistingElementIsSaved(TestHelper::createImageAsset());
    }

    public function testExistingPageIsSaved(): void
    {
        $this->assertExistingElementIsSaved(TestHelper::createEmptyDocumentPage());
    }

    private function performSanityCheck(ElementInterface $element): void
    {
        $method = new ReflectionMethod(SanityCheckHandler::class, 'performSanityCheck');
        $method->invoke(new SanityCheckHandler(), $element);
    }

    private function countRows(int $id, string $table = 'objects'): int
    {
        return (int) Db::get()->fetchOne('SELECT COUNT(*) FROM ' . $table . ' WHERE id = ?', [$id]);
    }

    private function assertDeletedElementIsNotReInserted(ElementInterface $element, string $table): void
    {
        $id = $element->getId();
        $stale = Service::getElementById(Service::getElementType($element), $id, ['force' => true]);
        $this->assertInstanceOf(ElementInterface::class, $stale);

        $element->delete();
        $this->assertSame(0, $this->countRows($id, $table));

        $this->performSanityCheck($stale);

        $this->assertSame(0, $this->countRows($id, $table), 'the sanity check must not re-insert a deleted element');
    }

    private function assertElementWhoseParentRowIsGoneIsNotSaved(ElementInterface $element, int $parentId, string $table): void
    {
        $id = $element->getId();
        $type = Service::getElementType($element);
        $versionCount = $element->getVersionCount();
        $stale = Service::getElementById($type, $id, ['force' => true]);
        $this->assertInstanceOf(ElementInterface::class, $stale);

        Db::get()->delete($table, ['id' => $parentId]);

        try {
            $this->performSanityCheck($stale);

            $this->assertSame(1, $this->countRows($id, $table));
            $this->assertSame($versionCount, (int) Db::get()->fetchOne('SELECT versionCount FROM ' . $table . ' WHERE id = ?', [$id]));
            $this->assertSame(0, (int) Db::get()->fetchOne('SELECT COUNT(*) FROM versions WHERE ctype = ? AND cid = ? AND note = ?', [$type, $id, 'Sanity Check']));
        } finally {
            Db::get()->delete($table, ['id' => $id]);
            Db::get()->delete('versions', ['ctype' => $type, 'cid' => $id]);
        }
    }

    private function assertExistingElementIsSaved(ElementInterface $element): void
    {
        $type = Service::getElementType($element);
        $versionCount = $element->getVersionCount();

        $this->performSanityCheck(Service::getElementById($type, $element->getId(), ['force' => true]));

        $saved = Service::getElementById($type, $element->getId(), ['force' => true]);
        $this->assertInstanceOf(ElementInterface::class, $saved);
        $this->assertGreaterThan($versionCount, $saved->getVersionCount());
    }
}
