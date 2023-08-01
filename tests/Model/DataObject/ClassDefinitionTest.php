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

namespace Pimcore\Tests\Model\DataObject;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Tests\Support\Test\ModelTestCase;

/**
 * Class ObjectTest
 *
 * @package Pimcore\Tests\Model\DataObject
 *
 * @group model.dataobject.object
 */
class ClassDefinitionTest extends ModelTestCase
{
    private function testSetterCode(string $fieldName, string $expectedSetterCode, bool $localizedField = false): void
    {
        $class = ClassDefinition::getByName('unittest');
        if ($localizedField) {
            $fd = $class->getFieldDefinition('localizedfields')->getFieldDefinition($fieldName);
        } else {
            $fd = $class->getFieldDefinition($fieldName);
        }
        $setterCode = $fd->getSetterCode($class);
        $this->assertEquals($expectedSetterCode, $setterCode);
    }

    /**
     * Verifies that the class definition gets renamed properly
     */
    public function testRename(): void
    {
        $class = ClassDefinition::getByName('unittest');
        $class->rename('unittest_renamed');

        $renamedClass = ClassDefinition::getByName('unittest_renamed');
        $renamedClass->rename('unittest');
    }

    /**
     * Verifies that the setter code gets created properly
     */
    public function testInputSetterCode(): void
    {
        $expectedSetterCode =
            '/**
* Set input - input
* @param string|null $input
* @return $this
*/
public function setInput(?string $input): static
{
	$this->markFieldDirty("input", true);

	$this->input = $input;

	return $this;
}

';
        $this->testSetterCode('input', $expectedSetterCode);
    }

    /**
     * Verifies that the setter code gets created properly
     */
    public function testFieldCollectionSetterCode(): void
    {
        $expectedSetterCode =
            '/**
* Set fieldcollection - fieldcollection
* @param \Pimcore\Model\DataObject\Fieldcollection|null $fieldcollection
* @return $this
*/
public function setFieldcollection(?\Pimcore\Model\DataObject\Fieldcollection $fieldcollection): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections $fd */
	$fd = $this->getClass()->getFieldDefinition("fieldcollection");
	$this->fieldcollection = $fd->preSetData($this, $fieldcollection);
	return $this;
}

';
        $this->testSetterCode('fieldcollection', $expectedSetterCode);
    }

    /**
     * Verifies that the setter code gets created properly
     */
    public function testBricksSetterCode(): void
    {
        $expectedSetterCode =
            '/**
* Set mybricks - mybricks
* @param \Pimcore\Model\DataObject\Objectbrick|null $mybricks
* @return $this
*/
public function setMybricks(?\Pimcore\Model\DataObject\Objectbrick $mybricks): static
{
	/** @var \Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks $fd */
	$fd = $this->getClass()->getFieldDefinition("mybricks");
	$this->mybricks = $fd->preSetData($this, $mybricks);
	return $this;
}

';
        $this->testSetterCode('mybricks', $expectedSetterCode);
    }

    /**
     * Verifies that the setter code gets created properly
     */
    public function testQuantityValueSetterCode(): void
    {
        $expectedSetterCode =
            '/**
* Set quantityValue - quantityValue
* @param \Pimcore\Model\DataObject\Data\QuantityValue|null $quantityValue
* @return $this
*/
public function setQuantityValue(?\Pimcore\Model\DataObject\Data\QuantityValue $quantityValue): static
{
	$this->markFieldDirty("quantityValue", true);

	$this->quantityValue = $quantityValue;

	return $this;
}

';
        $this->testSetterCode('quantityValue', $expectedSetterCode);
    }

    /**
     * Verifies that the setter code gets created properly
     */
    public function testLocalizedFieldSetterCode(): void
    {
        $expectedSetterCode =
            '/**
* Set linput - linput
* @param string|null $linput
* @return $this
*/
public function setLinput(?string $linput): static
{
	$this->markFieldDirty("linput", true);

	$this->linput = $linput;

	return $this;
}

';
        $this->testSetterCode('linput', $expectedSetterCode, true);
    }
}
