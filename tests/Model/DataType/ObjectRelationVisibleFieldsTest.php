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

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\BooleanSelect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\VisibleFieldDefinitionHelper;
use Pimcore\Tests\Support\Test\ModelTestCase;

/**
 * Covers the visible-field definitions built for the object relation types, in particular that
 * top-level and localized fields are described the same way and that the advanced type resolves
 * its class through allowedClassId.
 *
 * @group dataTypeLocal
 */
class ObjectRelationVisibleFieldsTest extends ModelTestCase
{
    private const CLASS_NAME = 'VisibleFieldsTest';

    protected function setUpTestClasses(): void
    {
        $this->tester->setupPimcoreClass_VisibleFieldsTest();
    }

    private function testClass(): ClassDefinition
    {
        $class = ClassDefinition::getByName(self::CLASS_NAME);
        $this->assertInstanceOf(ClassDefinition::class, $class);

        return $class;
    }

    /**
     * @param array<string, array<string, mixed>> $definitions
     */
    private function assertLocalizedAndTopLevelDescribedAlike(array $definitions): void
    {
        $this->assertSame(
            ['plainInput', 'plainBool', 'plainSelect', 'linput', 'lbool', 'lselect'],
            array_keys($definitions)
        );

        foreach ($definitions as $name => $definition) {
            $this->assertSame($name, $definition['name']);
            $this->assertTrue($definition['noteditable'], "$name must be read-only");
        }

        $this->assertSame('input', $definitions['plainInput']['fieldtype']);
        $this->assertSame('input', $definitions['linput']['fieldtype']);
        $this->assertArrayNotHasKey('options', $definitions['plainInput']);
        $this->assertArrayNotHasKey('options', $definitions['linput']);

        // a localized BooleanSelect carries its options exactly like a top-level one
        $this->assertSame('booleanSelect', $definitions['plainBool']['fieldtype']);
        $this->assertSame('booleanSelect', $definitions['lbool']['fieldtype']);
        $this->assertSame(BooleanSelect::DEFAULT_OPTIONS, $definitions['plainBool']['options']);
        $this->assertSame(BooleanSelect::DEFAULT_OPTIONS, $definitions['lbool']['options']);
        $this->assertArrayNotHasKey('optionsProviderClass', $definitions['lbool']);

        // selects carry options and their options provider, top-level and localized alike
        $this->assertSame('select', $definitions['plainSelect']['fieldtype']);
        $this->assertSame('select', $definitions['lselect']['fieldtype']);
        $this->assertSame(['1', '2'], array_column($definitions['plainSelect']['options'], 'value'));
        $this->assertSame(['l1', 'l2'], array_column($definitions['lselect']['options'], 'value'));
        $this->assertArrayHasKey('optionsProviderClass', $definitions['plainSelect']);
        $this->assertArrayHasKey('optionsProviderClass', $definitions['lselect']);
    }

    public function testManyToManyObjectRelationDescribesTopLevelAndLocalizedFieldsAlike(): void
    {
        $fd = new ManyToManyObjectRelation();
        $fd->setClasses([['classes' => self::CLASS_NAME]]);
        $fd->setVisibleFields('plainInput,plainBool,plainSelect,linput,lbool,lselect');

        $fd->enrichLayoutDefinition(null);

        $this->assertLocalizedAndTopLevelDescribedAlike($fd->visibleFieldDefinitions);
    }

    public function testAdvancedManyToManyObjectRelationResolvesTheClassThroughAllowedClassId(): void
    {
        $fd = new AdvancedManyToManyObjectRelation();
        $fd->setClasses([]);
        $fd->setAllowedClassId(self::CLASS_NAME);
        $fd->setVisibleFields('plainInput,plainBool,plainSelect,linput,lbool,lselect');

        $fd->enrichLayoutDefinition(null);
        $this->assertLocalizedAndTopLevelDescribedAlike($fd->visibleFieldDefinitions);

        // without an allowed class nothing is resolved, even if "classes" is set
        $noClass = new AdvancedManyToManyObjectRelation();
        $noClass->setClasses([['classes' => self::CLASS_NAME]]);
        $noClass->setAllowedClassId(null);
        $noClass->setVisibleFields('plainInput');
        $noClass->enrichLayoutDefinition(null);

        $this->assertSame([], $noClass->visibleFieldDefinitions);
    }

    public function testUnresolvedFieldFallsBackToReadOnlyInput(): void
    {
        $fd = new ManyToManyObjectRelation();
        $fd->setClasses([['classes' => self::CLASS_NAME]]);
        $fd->setVisibleFields('plainInput,doesNotExist');

        $fd->enrichLayoutDefinition(null);

        $definition = $fd->visibleFieldDefinitions['doesNotExist'];
        $this->assertSame('doesNotExist', $definition['name']);
        $this->assertSame('input', $definition['fieldtype']);
        $this->assertTrue($definition['noteditable']);
        $this->assertIsString($definition['title']);
        $this->assertArrayNotHasKey('options', $definition);
    }

    public function testNothingIsResolvedWithoutVisibleFieldsOrClass(): void
    {
        $fd = new ManyToManyObjectRelation();
        $fd->setClasses([['classes' => self::CLASS_NAME]]);
        $fd->setVisibleFields(null);
        $fd->enrichLayoutDefinition(null);
        $this->assertSame([], $fd->visibleFieldDefinitions);

        $fd = new ManyToManyObjectRelation();
        $fd->setClasses([]);
        $fd->setVisibleFields('plainInput');
        $fd->enrichLayoutDefinition(null);
        $this->assertSame([], $fd->visibleFieldDefinitions);

        $fd = new ManyToManyObjectRelation();
        $fd->setClasses([['classes' => 'DoesNotExistClass']]);
        $fd->setVisibleFields('plainInput');
        $fd->enrichLayoutDefinition(null);
        $this->assertSame([], $fd->visibleFieldDefinitions);
    }

    public function testHelperBuildingBlocks(): void
    {
        $class = $this->testClass();

        $this->assertSame($class->getId(), VisibleFieldDefinitionHelper::resolveClass(self::CLASS_NAME)?->getId());
        if (is_numeric($class->getId())) {
            // numeric identifiers are looked up as class ids
            $this->assertSame($class->getId(), VisibleFieldDefinitionHelper::resolveClass($class->getId())?->getId());
        }
        $this->assertNull(VisibleFieldDefinitionHelper::resolveClass(null));
        $this->assertNull(VisibleFieldDefinitionHelper::resolveClass(''));
        $this->assertNull(VisibleFieldDefinitionHelper::resolveClass('DoesNotExistClass'));

        $this->assertInstanceOf(Input::class, VisibleFieldDefinitionHelper::findClassFieldDefinition($class, 'plainInput'));
        $this->assertInstanceOf(BooleanSelect::class, VisibleFieldDefinitionHelper::findClassFieldDefinition($class, 'lbool'));
        $this->assertNull(VisibleFieldDefinitionHelper::findClassFieldDefinition($class, 'doesNotExist'));

        $input = (new Input())->setName('someInput')->setTitle('Some input');
        $this->assertSame(
            ['name' => 'someInput', 'title' => 'Some input', 'fieldtype' => 'input', 'noteditable' => true],
            VisibleFieldDefinitionHelper::buildDefinition($input)
        );

        $fallback = VisibleFieldDefinitionHelper::buildFallbackDefinition('unknown');
        $this->assertSame('unknown', $fallback['name']);
        $this->assertSame('input', $fallback['fieldtype']);
        $this->assertTrue($fallback['noteditable']);
    }
}
