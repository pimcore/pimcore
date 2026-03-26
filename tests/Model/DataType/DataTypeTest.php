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
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Tests\Support\Test\DataType\AbstractDataTypeTestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * @group dataTypeLocal
 */
class DataTypeTest extends AbstractDataTypeTestCase
{
    /**
     * Creates and saves object locally without testing against a comparison object
     */
    protected function createTestObject(array|string $fields = [], ?array &$returnData = []): Unittest
    {
        $object = TestHelper::createEmptyObject('local', true, true);
        if ($fields) {
            if (isset(func_get_args()[1])) {
                $this->fillObject($object, $fields, $returnData);
            } else {
                $this->fillObject($object, $fields);
            }
        }

        $object->save();

        $this->assertNotNull($object);
        $this->assertInstanceOf(Unittest::class, $object);

        $this->testObject = $object;

        return $this->testObject;
    }

    public function refreshObject(): void
    {
        $this->testObject = AbstractObject::getById($this->testObject->getId(), ['force' => true]);
    }
}
