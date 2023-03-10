<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tests\Model;

use Pimcore\Bundle\EcommerceFrameworkBundle\Tests\Support\Helper\TestDataHelper;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * @group dataTypeLocal
 */
class DataTypeTest extends TestCase
{
    protected bool $cleanupDbInSetup = true;

    protected TestDataHelper $testDataHelper;

    public function _inject(TestDataHelper $testData)
    {
        $this->testDataHelper = $testData;
    }

    protected int $seed = 1;

    protected Unittest $testObject;

    public function testIndexFieldSelectionField(): void
    {
        $this->createTestObject('indexFieldSelectionField');

        $this->refreshObject();
        $this->testDataHelper->assertIndexFieldSelectionField($this->testObject, 'indexFieldSelectionField', $this->seed);
    }

    public function testIndexFieldSelection(): void
    {
        $this->createTestObject('indexFieldSelection');

        $this->refreshObject();
        $this->testDataHelper->assertIndexFieldSelection($this->testObject, 'indexFieldSelection', $this->seed);
    }

    public function testIndexFieldSelectionCombo(): void
    {
        $this->createTestObject('indexFieldSelectionCombo');

        $this->refreshObject();
        $this->testDataHelper->assertIndexFieldSelectionCombo($this->testObject, 'indexFieldSelectionCombo', $this->seed);
    }

    /**
     * Calls fill* methods on the object as needed in test
     *
     */
    protected function fillObject(Concrete $object, array|string $fields = [], ?array &$returnData = []): void
    {
        // allow to pass only a string (e.g. input) -> fillInput($object, "input", $seed)
        if (!is_array($fields)) {
            $fields = [
                [
                    'method' => 'fill' . ucfirst($fields),
                    'field' => $fields,
                ],
            ];
        }

        if (!is_array($fields)) {
            throw new \InvalidArgumentException('Fields needs to be an array');
        }

        foreach ($fields as $field) {
            $method = $field['method'];

            if (!$method) {
                throw new \InvalidArgumentException(sprintf('Need a method to call'));
            }

            if (!method_exists($this->testDataHelper, $method)) {
                throw new \InvalidArgumentException(sprintf('Method %s does not exist', $method));
            }

            $methodArguments = [$object, $field['field'], $this->seed];

            $additionalArguments = $field['arguments'] ?? [];
            foreach ($additionalArguments as $aa) {
                $methodArguments[] = $aa;
            }
            if (isset(func_get_args()[2])) {
                $methodArguments[] = &$returnData;
            }

            call_user_func_array([$this->testDataHelper, $method], $methodArguments);
        }
    }

    /**
     * Creates and saves object locally without testing against a comparison object
     */
    protected function createTestObject($fields = [], &$returnData = []): Unittest
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
