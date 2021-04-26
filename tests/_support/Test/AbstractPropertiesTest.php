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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tests\Test;

use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Tests\Helper\Element\PropertiesTestHelper;
use Pimcore\Tests\Util\TestHelper;

abstract class AbstractPropertiesTest extends ModelTestCase
{
    /**
     * @var bool
     */
    protected $cleanupDbInSetup = true;

    /**
     * @var PropertiesTestHelper
     */
    protected $propertiesTestHelper;

    /** @var ElementInterface */
    protected $testElement;

    /**
     * @param PropertiesTestHelper $testData
     */
    public function _inject(PropertiesTestHelper $testHelper)
    {
        $this->propertiesTestHelper = $testHelper;
    }

    /**
     * @return ElementInterface
     */
    abstract public function createElement(): ElementInterface;

    /**
     * @return ElementInterface
     */
    abstract public function reloadElement(): ElementInterface;

    /**
     * {@inheritdoc}
     */
    protected function needsDb()
    {
        return true;
    }

    public function testCRUD()
    {
        // create and read
        $this->createElement();
        $expectedData = 'sometext' . uniqid();
        $this->testElement->setProperty('textproperty1', 'input', $expectedData . '_1');
        $this->testElement->setProperty('textproperty2', 'input', $expectedData . '_2');
        $this->testElement->save();

        $this->reloadElement();
        $this->assertTrue($this->testElement->hasProperty('textproperty1'));
        $actual = $this->testElement->getProperty('textproperty1');

        $this->assertEquals($expectedData . '_1', $actual);

        $actual = $this->testElement->getProperty('textproperty2');
        $expectedData2 = $expectedData;
        $this->assertEquals($expectedData . '_2', $actual);

        // update
        $expectedData = 'sometext' . uniqid() . '_new';
        $this->testElement->setProperty('textproperty1', 'input', $expectedData);
        $this->testElement->save();
        $this->reloadElement();
        $actual = $this->testElement->getProperty('textproperty1');
        $this->assertEquals($expectedData, $actual);

        // delete
        $this->testElement->setProperty('textproperty1', 'input', null);
        $this->testElement->save();

        $this->reloadElement();
        $actual = $this->testElement->getProperty('textproperty1');
        $this->assertEquals(null, $actual);

        $expectedData = 'sometext' . uniqid();
        $actual = $this->testElement->getProperty('textproperty2');
        $this->assertEquals($expectedData2 . '_2', $actual);
    }

    public function testInheritance()
    {
        // create and read
        $parentElement = $this->createElement();
        $childElement = $this->createElement();
        $childElement->setParentId($parentElement->getId());
        $childElement->save();
        $this->testElement = $parentElement;

        $expectedData = 'sometext' . uniqid();
        $this->testElement->setProperty('textproperty3', 'input', $expectedData . '_3', false, true);
        $this->testElement->save();

        $childElement = Service::getElementById(Service::getElementType($childElement), $childElement->getId(), true);
        $this->assertEquals($expectedData . '_3', $childElement->getProperty('textproperty3'));
    }

    public function testRelation()
    {
        $asset = TestHelper::createImageAsset();

        $this->createElement();
        $this->testElement->setProperty('assetProperty', 'asset', $asset);
        $this->testElement->save();
        $this->reloadElement();

        /** @var Asset $assetProperty */
        $assetProperty = $this->testElement->getProperty('assetProperty');
        $this->assertInstanceOf(Asset::class, $assetProperty);
        $this->assertEquals($asset->getId(), $assetProperty->getId());
    }
}
