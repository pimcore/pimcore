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

namespace Pimcore\Tests\Model\DataType;

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Inheritance;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tests\Test\AbstractPropertiesTest;
use Pimcore\Tests\Util\TestHelper;

/**
 * @group properties
 */
class PropertiesTest extends AbstractPropertiesTest
{
    public function createElement(): ElementInterface
    {
        $this->testElement = TestHelper::createEmptyObject('local', true, true, '\\Pimcore\\Model\\DataObject\\Inheritance');
        $this->testElement->save();

        $this->assertNotNull($this->testElement);
        $this->assertInstanceOf(Inheritance::class, $this->testElement);

        return $this->testElement;
    }

    public function reloadElement(): ElementInterface
    {
        $this->testElement = AbstractObject::getById($this->testElement->getId(), true);

        return $this->testElement;
    }
}
