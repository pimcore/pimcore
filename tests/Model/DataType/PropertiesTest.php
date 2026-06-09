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

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Inheritance;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tests\Support\Test\AbstractPropertiesTest;
use Pimcore\Tests\Support\Util\TestHelper;

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
        $this->testElement = AbstractObject::getById($this->testElement->getId(), ['force' => true]);

        return $this->testElement;
    }
}
