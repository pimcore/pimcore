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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tests\Support\Helper;

use Pimcore\Bundle\EcommerceFrameworkBundle\CoreExtensions\ObjectData\IndexFieldSelection;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Tests\Support\Helper\AbstractTestDataHelper;

class TestDataHelper extends AbstractTestDataHelper
{
    public function assertIndexFieldSelectionCombo(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();

        $this->assertIsEqual($object, $field, 'carClass', $value);
    }

    public function assertIndexFieldSelection(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        /** @var IndexFieldSelection $value */
        $value = $object->$getter();

        $this->assertInstanceOf(IndexFieldSelection::class, $value);

        $this->assertIsEqual($object, $field, 'carClass', $value->getField());
    }

    public function assertIndexFieldSelectionField(Concrete $object, string $field, int $seed = 1): void
    {
        $getter = 'get' . ucfirst($field);
        $value = $object->$getter();

        $this->assertIsEqual($object, $field, 'carClass,color', $value);
    }

    public function fillIndexFieldSelectionCombo(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter('carClass');
    }

    public function fillIndexFieldSelectionField(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $object->$setter('carClass,color');
    }

    public function fillIndexFieldSelection(Concrete $object, string $field, int $seed = 1): void
    {
        $setter = 'set' . ucfirst($field);
        $value = new IndexFieldSelection('', 'carClass', '');
        $object->$setter($value);
    }

    public function assertIsEqual(Concrete $object, string $field, mixed $expected, mixed $value): void
    {
        $fd = $this->getFieldDefinition($object, $field);
        if ($fd instanceof DataObject\ClassDefinition\Data\EqualComparisonInterface) {
            $this->assertTrue($fd->isEqual($expected, $value), sprintf('Expected isEqual() returns true for data type: %s', ucfirst($field)));
        }
    }

    public function getFieldDefinition(Concrete $object, string $field): ?Data
    {
        $cd = $object->getClass();
        $fd = $cd->getFieldDefinition($field);
        if (!$fd) {
            $localizedFields = $cd->getFieldDefinition('localizedfields');
            if ($localizedFields instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                $fd = $localizedFields->getFieldDefinition($field);
            }
        }

        return $fd;
    }
}
