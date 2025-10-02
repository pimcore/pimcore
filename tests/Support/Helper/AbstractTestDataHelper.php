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

namespace Pimcore\Tests\Support\Helper;

use Codeception\Module;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tests\Support\Util\TestHelper;

abstract class AbstractTestDataHelper extends Module
{
    public function assertElementsEqual(ElementInterface $e1, ElementInterface $e2): void
    {
        $this->assertEquals(get_class($e1), get_class($e2));
        $this->assertEquals($e1->getId(), $e2->getId());
        $this->assertEquals($e1->getType(), $e2->getType());
        $this->assertEquals($e1->getFullPath(), $e2->getFullPath());
    }

    public function assertObjectsEqual(AbstractObject $obj1, AbstractObject $obj2): void
    {
        $this->assertElementsEqual($obj1, $obj2);

        $str1 = TestHelper::createObjectComparisonString($obj1);
        $str2 = TestHelper::createObjectComparisonString($obj2);

        $this->assertNotNull($str1);
        $this->assertNotNull($str2);

        $this->assertEquals($str1, $str2);
    }

    protected function getObjectList(?string $condition = null): array
    {
        $list = new DataObject\Listing();
        $list->setOrderKey('id');
        if (isset($condition)) {
            $list->setCondition($condition);
        }

        $objects = $list->load();

        return $objects;
    }
}
