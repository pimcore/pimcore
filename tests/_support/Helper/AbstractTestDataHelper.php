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

namespace Pimcore\Tests\Helper;

use Codeception\Module;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tests\Util\TestHelper;

abstract class AbstractTestDataHelper extends Module
{
    public function assertElementsEqual(ElementInterface $e1, ElementInterface $e2)
    {
        $this->assertEquals(get_class($e1), get_class($e2));
        $this->assertEquals($e1->getId(), $e2->getId());
        $this->assertEquals($e1->getType(), $e2->getType());
        $this->assertEquals($e1->getFullPath(), $e2->getFullPath());
    }

    public function assertObjectsEqual(AbstractObject $obj1, AbstractObject $obj2)
    {
        $this->assertElementsEqual($obj1, $obj2);

        $str1 = TestHelper::createObjectComparisonString($obj1);
        $str2 = TestHelper::createObjectComparisonString($obj2);

        $this->assertNotNull($str1);
        $this->assertNotNull($str2);

        $this->assertEquals($str1, $str2);
    }

    /**
     * @param string|null $condition
     *
     * @return Concrete[]
     */
    protected function getObjectList($condition = null)
    {
        $list = new DataObject\Listing();
        $list->setOrderKey('o_id');
        $list->setCondition($condition);

        $objects = $list->load();

        return $objects;
    }
}
