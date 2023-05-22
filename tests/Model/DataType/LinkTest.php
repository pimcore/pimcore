<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Tests\Model\DataType;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Data\Link;
use Pimcore\Model\DataObject\Service;
use Pimcore\Model\DataObject\unittestLink;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Tests\Test\ModelTestCase;
use Pimcore\Tests\Util\TestHelper;

/**
 * Class LinkTest
 *
 * @group model.datatype.link
 */
class LinkTest extends ModelTestCase
{
    protected Asset $testAsset;

    protected Link $link;

    protected Data $linkDefinition;

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

    protected function setUpTestClasses()
    {
        $this->tester->setupPimcoreClass_Link();
    }

    protected function setupInternalLinkObjects()
    {
        $this->testAsset = TestHelper::createImageAsset();

        $link = new Link();
        $link->setInternal($this->testAsset->getId());
        $link->setInternalType('asset');
        $this->link = $link;

        $linkObject = $this->createLinkObject();
        $linkObject->setTestlink($link);
        $this->linkDefinition = $linkObject->getClass()->getFieldDefinition('testlink');
    }

    /**
     * @return unittestLink
     *
     * @throws \Exception
     */
    protected function createLinkObject()
    {
        $object = new unittestLink();
        $object->setParent(Service::createFolderByPath('/links'));
        $object->setKey('link1');
        $object->setPublished(true);

        return $object;
    }

    /**
     * Verifies that Link data is loaded correctly after save and reload
     *
     * @throws \Exception
     */
    public function testSave()
    {
        $linkObject = $this->createLinkObject();
        $link = new Link();
        $link->setDirect('https://pimcore.com/');
        $linkObject->setTestlink($link);
        $linkObject->setLtestlink($link);
        $linkObject->save();

        $linkObjectReloaded = unittestLink::getById($linkObject->getId(), ['force' => true]);

        $this->assertEquals($link->getDirect(), $linkObjectReloaded->getTestlink()->getDirect());
        $this->assertEquals($link->getDirect(), $linkObjectReloaded->getLtestlink()->getDirect());
    }

    /**
     * Verifies that Internal Link data throws correct exceptions if invalid data is given
     *
     * @throws \Exception
     */
    public function testInternalCheckValidity()
    {
        $this->setupInternalLinkObjects();
        $this->testAsset->delete();

        //Should return validation exception as asset was deleted
        $this->expectException(ValidationException::class);
        $this->linkDefinition->checkValidity($this->link);
    }

    public function testAsset()
    {
        $this->setupInternalLinkObjects();
        $this->testAsset->delete();
        //Should not return validation exception as parameter is set
        $this->linkDefinition->checkValidity($this->link, true, ['resetInvalidFields' => true]);

        //Should return sanitized link data
        $this->assertTrue($this->link->getInternal() === null);
        $this->assertTrue($this->link->getInternalType() === null);
    }

    /**
     * Verifies that Link data throws correct exceptions if invalid data is given
     *
     * @throws \Exception
     */
    public function testCheckValidity()
    {
        $linkObject = $this->createLinkObject();
        $linkObject->setTestlink('https://pimcore.com/');
        $linkObject->setLtestlink('https://pimcore.com/');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Expected DataObject\\Data\\Link or null');

        $linkObject->save();
    }
}
