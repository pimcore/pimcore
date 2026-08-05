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

namespace Pimcore\Tests\Model\Document;

use Pimcore\Model\Document;
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
        $this->testElement = TestHelper::createDocumentFolder();
        $this->testElement->save();

        $this->assertNotNull($this->testElement);
        $this->assertInstanceOf(Document::class, $this->testElement);

        return $this->testElement;
    }

    public function reloadElement(): ElementInterface
    {
        $this->testElement = Document::getById($this->testElement->getId(), ['force' => true]);

        return $this->testElement;
    }
}
