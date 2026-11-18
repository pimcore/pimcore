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

namespace Pimcore\Tests\Model\DataType;

use Exception;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\DataObject\UnittestLazyBlock;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * A lazy loading Block inside localizedfields.
 *
 * @group model.datatype.block
 */
class LazyLocalizedBlockTest extends ModelTestCase
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
        $this->tester->setupFieldcollection_LazyBlockFc();
        $this->tester->setupPimcoreClass_LazyLocalizedBlock();
    }

    protected function createObject(string $key, ?UnittestLazyBlock $parent = null): UnittestLazyBlock
    {
        $object = new UnittestLazyBlock();
        $object->setParent($parent ?? Service::createFolderByPath('/lazylocalizedblock'));
        $object->setKey($key);
        $object->setPublished(true);

        return $object;
    }

    /**
     * @return array<int, array<string, BlockElement>>
     */
    protected function blockItems(string $text): array
    {
        return [['lazyblockinput' => new BlockElement('lazyblockinput', 'input', $text)]];
    }

    /**
     * @throws Exception
     */
    public function testSaveAndReloadLazyBlockInLocalizedfields(): void
    {
        $object = $this->createObject('save');
        $object->setLazyblock($this->blockItems('hello en'), 'en');
        $object->setLazyblock($this->blockItems('hello de'), 'de');
        $object->save();

        RuntimeCache::clear();
        $reloaded = DataObject::getById($object->getId(), ['force' => true]);

        $this->assertSame('hello en', $reloaded->getLazyblock('en')[0]['lazyblockinput']->getData());
        $this->assertSame('hello de', $reloaded->getLazyblock('de')[0]['lazyblockinput']->getData());

        // saving the reloaded (still lazy) object must keep the data
        $reloaded->save();
        RuntimeCache::clear();
        $reloaded = DataObject::getById($object->getId(), ['force' => true]);
        $this->assertSame('hello en', $reloaded->getLazyblock('en')[0]['lazyblockinput']->getData());
    }

    /**
     * @throws Exception
     */
    public function testInheritedLazyBlockInLocalizedfields(): void
    {
        $parent = $this->createObject('parent');
        $parent->setLazyblock($this->blockItems('from parent'), 'en');
        $parent->save();

        $child = $this->createObject('child', $parent);
        $child->save();

        RuntimeCache::clear();
        $child = DataObject::getById($child->getId(), ['force' => true]);

        $inheritedState = DataObject::getGetInheritedValues();
        DataObject::setGetInheritedValues(true);

        try {
            $this->assertSame('from parent', $child->getLazyblock('en')[0]['lazyblockinput']->getData());
        } finally {
            DataObject::setGetInheritedValues($inheritedState);
        }
    }

    /**
     * @throws Exception
     */
    public function testInheritedLazyBlockWithoutChildLanguageRow(): void
    {
        $parent = $this->createObject('parent');
        $parent->setLazyblock($this->blockItems('from parent'), 'en');
        $parent->save();

        $child = $this->createObject('child', $parent);
        $child->setLazyblock($this->blockItems('child de'), 'de');
        $child->save();

        RuntimeCache::clear();
        $child = DataObject::getById($child->getId(), ['force' => true]);

        $inheritedState = DataObject::getGetInheritedValues();
        DataObject::setGetInheritedValues(true);

        try {
            $this->assertSame('from parent', $child->getLazyblock('en')[0]['lazyblockinput']->getData());
        } finally {
            DataObject::setGetInheritedValues($inheritedState);
        }
    }

    /**
     * @throws Exception
     */
    public function testLazyBlockInLocalizedfieldsOfFieldcollection(): void
    {
        $object = $this->createObject('fieldcollection');
        $items = new Fieldcollection();
        $withValue = new Fieldcollection\Data\LazyBlockFc();
        $withValue->setLazyblock($this->blockItems('in collection'), 'en');
        $items->add($withValue);
        $items->add(new Fieldcollection\Data\LazyBlockFc());
        $object->setLazyfc($items);
        $object->save();

        RuntimeCache::clear();
        $reloaded = DataObject::getById($object->getId(), ['force' => true]);

        $reloadedItems = $reloaded->getLazyfc()->getItems();
        $this->assertSame('in collection', $reloadedItems[0]->getLazyblock('en')[0]['lazyblockinput']->getData());
        $this->assertSame([], $reloadedItems[1]->getLazyblock('en'));

        // saving the reloaded (still lazy) object must keep the data
        $reloaded->save();
        RuntimeCache::clear();
        $reloaded = DataObject::getById($object->getId(), ['force' => true]);
        $this->assertSame('in collection', $reloaded->getLazyfc()->getItems()[0]->getLazyblock('en')[0]['lazyblockinput']->getData());
    }
}
