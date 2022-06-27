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
