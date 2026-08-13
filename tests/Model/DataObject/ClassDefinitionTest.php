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

namespace Pimcore\Tests\Model\DataObject;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
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

    private function testGetterCode(string $fieldName, string $expectedGetterCode, bool $localizedField = false): void
    {
        $class = ClassDefinition::getByName('unittest');
        if ($localizedField) {
            $fd = $class->getFieldDefinition('localizedfields')->getFieldDefinition($fieldName);
            $getterCode = $fd->getGetterCodeLocalizedfields($class);
        } else {
            $fd = $class->getFieldDefinition($fieldName);
            $getterCode = $fd->getGetterCode($class);
        }
        $this->assertEquals($expectedGetterCode, $getterCode);
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
* @param \Pimcore\Model\DataObject\Fieldcollection<\Pimcore\Model\DataObject\Fieldcollection\Data\Unittestfieldcollection>|null $fieldcollection
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

    public function testFieldCollectionPhpdocTypeWithoutAllowedTypes(): void
    {
        $fieldDefinition = new Fieldcollections();

        $this->assertSame(
            '\Pimcore\Model\DataObject\Fieldcollection|null',
            $fieldDefinition->getPhpdocInputType()
        );
        $this->assertSame(
            '\Pimcore\Model\DataObject\Fieldcollection|null',
            $fieldDefinition->getPhpdocReturnType()
        );
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

    /**
     * Verifies that the getter code gets created properly and that the
     * PreGetValueHook is called before actually getting the data
     */
    public function testLocalizedFieldGetterCode(): void
    {
        $expectedGetterCode =
            '/**
* Get linput - linput
* @return string|null
*/
public function getLinput(?string $language = null): ?string
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("linput");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getLocalizedfields()->getLocalizedValue("linput", $language);
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain();
	}

	return $data;
}

';
        $this->testGetterCode('linput', $expectedGetterCode, true);
    }

    /**
     * Verifies that the getter code gets created properly and that the
     * PreGetValueHook is called before actually getting the data
     */
    public function testLocalizedTableGetterCode(): void
    {
        $expectedGetterCode =
            '/**
* Get ltable - ltable
* @return array
*/
public function getLtable (?string $language = null): array
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("ltable");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->getLocalizedfields()->getLocalizedValue("ltable", $language);
	if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
		return $data->getPlain() ?? [];
	}
	return $data ?? [];
}

';
        $this->testGetterCode('ltable', $expectedGetterCode, true);
    }

    /**
     * Verifies that the getter code gets created properly and that the
     * PreGetValueHook is called before actually getting the data
     * (i.e. before the object brick container is lazily initialized)
     */
    public function testBricksGetterCode(): void
    {
        $expectedGetterCode =
            '/**
* @return \Pimcore\Model\DataObject\Unittest\Mybricks
*/
public function getMybricks(): ?\Pimcore\Model\DataObject\Objectbrick
{
	if ($this instanceof PreGetValueHookInterface && !\Pimcore::inAdmin()) {
		$preValue = $this->preGetValue("mybricks");
		if ($preValue !== null) {
			return $preValue;
		}
	}

	$data = $this->mybricks;
	if (!$data) {
		if (\Pimcore\Tool::classExists("\\\\Pimcore\\\\Model\\\\DataObject\\\\Unittest\\\\Mybricks")) {
			$data = new \Pimcore\Model\DataObject\Unittest\Mybricks($this, "mybricks");
			$this->mybricks = $data;
		} else {
			return null;
		}
	}
	return $data;
}

';
        $this->testGetterCode('mybricks', $expectedGetterCode);
    }

    public function testInputEmptyDefaultValueIsNormalizedToNullAfterImportAndReload(): void
    {
        $class = ClassDefinition::getByName('unittest');
        $this->assertInstanceOf(ClassDefinition::class, $class);

        $originalClassDefinition = ClassDefinition\Service::generateClassDefinitionJson($class);
        $classDefinition = json_decode($originalClassDefinition, true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($this->setInputDefaultValueAndUnique($classDefinition['layoutDefinitions'], 'input'));

        try {
            $this->assertTrue(ClassDefinition\Service::importClassDefinitionFromJson($class, json_encode($classDefinition, JSON_THROW_ON_ERROR), true));

            $reloadedClass = ClassDefinition::getById($class->getId(), true);
            $this->assertInstanceOf(ClassDefinition::class, $reloadedClass);

            $inputField = $reloadedClass->getFieldDefinition('input');
            $this->assertInstanceOf(Input::class, $inputField);
            $this->assertTrue($inputField->getUnique());
            $this->assertNull($inputField->getDefaultValue());
        } finally {
            ClassDefinition\Service::importClassDefinitionFromJson($class, $originalClassDefinition, true);
        }
    }

    private function setInputDefaultValueAndUnique(array &$layoutDefinition, string $fieldName): bool
    {
        if (($layoutDefinition['name'] ?? null) === $fieldName && ($layoutDefinition['fieldtype'] ?? null) === 'input') {
            $layoutDefinition['unique'] = true;
            $layoutDefinition['defaultValue'] = '';

            return true;
        }

        if (isset($layoutDefinition['children']) && is_array($layoutDefinition['children'])) {
            foreach ($layoutDefinition['children'] as &$child) {
                if (is_array($child) && $this->setInputDefaultValueAndUnique($child, $fieldName)) {
                    return true;
                }
            }
            unset($child);
        }

        return false;
    }
}
