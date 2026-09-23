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

use Exception;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Model\DataObject\Unittest;
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
     * The relation "allowed classes" list is attacker-controlled - any backend user holding only
     * the granular "classes" permission can edit it - and it is concatenated, unvalidated, into the
     * PHPDoc type that Relation::getPhpDocClassString() builds. Until GHSA-f4jp-qhv6-g8gq /
     * GHSA-9r9j-g82w-9578 that type was emitted verbatim on the generated getter's @return and the
     * setter's @param line, so an entry carrying a docblock terminator closed the comment early and
     * dropped attacker tokens into the class body of a generated, autoloaded model class.
     *
     * @dataProvider maliciousAllowedClassProvider
     */
    public function testRelationGetterAndSetterSanitizeThePhpdocType(string $maliciousAllowedClass): void
    {
        $class = ClassDefinition::getByName('unittest');

        // The type builder itself is deliberately left unguarded - the fix sits at the emission
        // site - so the raw type still carries the payload.
        $malicious = $this->relationFieldDefinition($maliciousAllowedClass);
        $this->assertStringContainsString('INJECTED', (string)$malicious->getPhpdocReturnType());

        // The generated setter carries a second, generator-owned docblock (the `@var $fd` hint),
        // so compare against a benign field rather than against a hard-coded delimiter count.
        $benign = $this->relationFieldDefinition('TargetClass');

        foreach (['getter', 'setter'] as $accessor) {
            $maliciousCode = $this->relationAccessorCode($malicious, $class, $accessor);
            $benignCode = $this->relationAccessorCode($benign, $class, $accessor);

            foreach (['/**', '*' . '/', '//'] as $delimiter) {
                $this->assertSame(
                    substr_count($benignCode, $delimiter),
                    substr_count($maliciousCode, $delimiter),
                    "the payload must not add a \"$delimiter\" to the generated $accessor code"
                );
            }

            $this->assertStringNotContainsString(
                '*' . '/',
                $this->extractPhpdocType($maliciousCode, $accessor),
                "the emitted $accessor PHPDoc type must not contain a docblock terminator"
            );
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function maliciousAllowedClassProvider(): iterable
    {
        yield 'plain terminator' => ['Foo ' . '*' . "/ } echo 'INJECTED'; __halt_compiler();"];

        // A single str_replace() pass turns this into a live terminator instead of removing it:
        // dropping the inner one splices the outer asterisk and slash back together.
        yield 'terminator spliced by a single sanitisation pass' => ['Foo **' . "// } echo 'INJECTED'; __halt_compiler();"];
    }

    /**
     * Negative control: a legitimate allowed-classes entry must still produce its unmangled
     * fully-qualified type on both the getter and the setter.
     */
    public function testRelationGetterAndSetterKeepABenignPhpdocTypeIntact(): void
    {
        $class = ClassDefinition::getByName('unittest');
        $fieldDefinition = $this->relationFieldDefinition('TargetClass');

        $this->assertSame(
            '\\Pimcore\\Model\\DataObject\\TargetClass|null',
            $this->extractPhpdocType($fieldDefinition->getGetterCode($class), 'getter')
        );
        $this->assertSame(
            '\\Pimcore\\Model\\DataObject\\TargetClass|null',
            $this->extractPhpdocType($fieldDefinition->getSetterCode($class), 'setter')
        );
    }

    private function relationFieldDefinition(string $allowedClass): ManyToOneRelation
    {
        $fieldDefinition = new ManyToOneRelation();
        $fieldDefinition->setName('myRelation');
        $fieldDefinition->setTitle('My Relation');
        $fieldDefinition->setObjectsAllowed(true);
        $fieldDefinition->setClasses([['classes' => $allowedClass]]);

        return $fieldDefinition;
    }

    private function relationAccessorCode(ManyToOneRelation $fieldDefinition, ClassDefinition $class, string $accessor): string
    {
        return $accessor === 'getter'
            ? $fieldDefinition->getGetterCode($class)
            : $fieldDefinition->getSetterCode($class);
    }

    private function extractPhpdocType(string $generatedCode, string $accessor): string
    {
        $pattern = $accessor === 'getter'
            ? '/^\* @return (.+)$/m'
            : '/^\* @param (.+) \$myRelation$/m';

        $this->assertSame(1, preg_match($pattern, $generatedCode, $match), "no $accessor PHPDoc type line found");

        return $match[1];
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
     * rename() deletes the class's PHP files and renames every persisted object's className via
     * raw SQL before ever calling save() - the method where the candidate name is actually
     * validated. A rejected rename must not leave either side effect applied.
     */
    public function testRenameToReservedWordLeavesClassAndObjectsUnchanged(): void
    {
        $class = ClassDefinition::getByName('unittest');

        $object = new Unittest();
        $object->setOmitMandatoryCheck(true);
        $object->setParentId(1);
        $object->setUserOwner(1);
        $object->setKey('reserved-word-rename-test-' . uniqid());
        $object->save();

        try {
            $class->rename('var');
            $this->fail('Expected renaming a class to a reserved word to throw.');
        } catch (Exception $exception) {
            $this->assertStringContainsString('reserved word', $exception->getMessage());
        }

        $this->assertSame('unittest', ClassDefinition::getByName('unittest')?->getName());

        $reloadedObject = Unittest::getById($object->getId(), ['force' => true]);
        $this->assertInstanceOf(
            Unittest::class,
            $reloadedObject,
            'The object must still resolve as Unittest - a rejected rename must not have renamed it in the database'
        );

        $object->delete();
    }

    /**
     * PCRE `$` also matches immediately before a trailing newline, so a class name, id or parent
     * class ending in "\n" passed the identifier checks in save() and reached the class-file
     * generator, which emits them verbatim into PHP source and file paths (GHSA-g2vm-g4vq-qhwj).
     *
     * @dataProvider trailingNewlineIdentifierProvider
     */
    public function testSaveRejectsIdentifiersWithTrailingNewline(string $name, string $id, string $parentClass): void
    {
        $class = new ClassDefinition();
        $class->setName($name);
        $class->setId($id);
        $class->setParentClass($parentClass);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('for class definition');

        $class->save();
    }

    public static function trailingNewlineIdentifierProvider(): array
    {
        return [
            'name' => ["TrailingNewlineName\n", 'TrailingNewlineName', ''],
            'id' => ['TrailingNewlineId', "TrailingNewlineId\n", ''],
            'parentClass' => ['TrailingNewlineParent', 'TrailingNewlineParent', "\\Pimcore\\Model\\DataObject\\Concrete\n"],
        ];
    }

    /**
     * A class name is emitted verbatim as the PHP class name in the generated class file, so a
     * PHP reserved word (e.g. "var") must be rejected at save time instead of reaching the class
     * file generator, where it produces a fatal syntax error only when an object of that class is
     * first instantiated (pimcore/platform-version#291).
     *
     * The same applies to a class already living in the `Pimcore\Model\DataObject` namespace the
     * generated class is emitted into - that one is shadowed by the generated file rather than
     * producing a syntax error. ReservedWordsHelperTest guards the full list.
     *
     * @dataProvider reservedWordClassNameProvider
     */
    public function testSaveRejectsReservedWordAsClassName(string $name, string $id): void
    {
        $class = new ClassDefinition();
        $class->setName($name);
        $class->setId($id);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('reserved word');

        $class->save();
    }

    public static function reservedWordClassNameProvider(): array
    {
        return [
            'php keyword' => ['var', 'ReservedWordVar'],
            'php keyword, mixed case' => ['Var', 'ReservedWordVarMixedCase'],
            'pimcore reserved word' => ['Folder', 'ReservedWordFolder'],
            'data object namespace class' => ['Service', 'ReservedWordService'],
            'data object namespace class, lower case' => ['listing', 'ReservedWordListing'],
            'data object namespace interface' => ['SelectOptionsInterface', 'ReservedWordSelectOptions'],
        ];
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
