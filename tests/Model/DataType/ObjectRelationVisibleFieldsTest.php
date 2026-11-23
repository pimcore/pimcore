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

use Pimcore;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\BooleanSelect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\VisibleFieldDefinitionHelper;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Tests\Support\Test\ModelTestCase;

/**
 * Covers the visible-field definitions built for the object relation types: the output of the previous
 * implementations is reproduced exactly, top-level and localized fields are described the same way,
 * unresolved names fall back to a plain input, the advanced type resolves its class through
 * allowedClassId, and subclasses keep working whether they override enrichLayoutDefinition() or
 * getVisibleFieldsClassIdentifier().
 *
 * @group dataTypeLocal
 */
class ObjectRelationVisibleFieldsTest extends ModelTestCase
{
    private const CLASS_NAME = 'VisibleFieldsTest';

    private const NUMERIC_CLASS_NAME = 'VisibleFieldsNumericIdTest';

    private const NUMERIC_CLASS_ID = '4711';

    protected function setUpTestClasses(): void
    {
        $this->tester->setupPimcoreClass_VisibleFieldsTest();
        $this->tester->setupPimcoreClass_VisibleFieldsNumericIdTest();
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

        // a numeric identifier is looked up as class id, not as class name
        $byId = new AdvancedManyToManyObjectRelation();
        $byId->setAllowedClassId(self::NUMERIC_CLASS_ID);
        $byId->setVisibleFields('plainInput,plainBool,plainSelect,linput,lbool,lselect');
        $byId->enrichLayoutDefinition(null);
        $this->assertLocalizedAndTopLevelDescribedAlike($byId->visibleFieldDefinitions);

        // without an allowed class nothing is resolved, even if "classes" is set
        $noClass = new AdvancedManyToManyObjectRelation();
        $noClass->setClasses([['classes' => self::CLASS_NAME]]);
        $noClass->setAllowedClassId(null);
        $noClass->setVisibleFields('plainInput');
        $noClass->enrichLayoutDefinition(null);

        $this->assertSame([], $noClass->visibleFieldDefinitions);
    }

    public function testManyToManyObjectRelationResolvesANumericClassIdentifier(): void
    {
        $fd = new ManyToManyObjectRelation();
        $fd->setClasses([['classes' => self::NUMERIC_CLASS_ID]]);
        $fd->setVisibleFields('plainInput,plainBool,plainSelect,linput,lbool,lselect');

        $fd->enrichLayoutDefinition(null);

        $this->assertLocalizedAndTopLevelDescribedAlike($fd->visibleFieldDefinitions);
    }

    public function testSubclassesChangeTheClassLookupThroughGetVisibleFieldsClassIdentifier(): void
    {
        // a subclass that takes the class from somewhere else than "classes" (the documented override point
        // for types that used to override enrichLayoutDefinition() only to change the lookup)
        $fd = new class(self::CLASS_NAME) extends ManyToManyObjectRelation {
            public function __construct(private readonly string $visibleFieldsClass)
            {
            }

            protected function getVisibleFieldsClassIdentifier(): ?string
            {
                return $this->visibleFieldsClass;
            }
        };
        $fd->setClasses([['classes' => 'DoesNotExistClass']]);
        $fd->setVisibleFields('plainInput,plainBool,plainSelect,linput,lbool,lselect');

        $fd->enrichLayoutDefinition(null);
        $this->assertLocalizedAndTopLevelDescribedAlike($fd->visibleFieldDefinitions);

        // the advanced type's own override is exactly that: allowedClassId instead of the first "classes" entry
        $advanced = new AdvancedManyToManyObjectRelation();
        $advanced->setClasses([['classes' => 'DoesNotExistClass']]);
        $advanced->setAllowedClassId(self::CLASS_NAME);
        $advanced->setVisibleFields('plainInput,linput');
        $advanced->enrichLayoutDefinition(null);
        $this->assertSame(['plainInput', 'linput'], array_keys($advanced->visibleFieldDefinitions));
    }

    public function testOutputIsIdenticalToThePreviousImplementations(): void
    {
        $class = $this->testClass();
        $input = $class->getFieldDefinition('plainInput');
        $bool = $class->getFieldDefinition('plainBool');
        $select = $class->getFieldDefinition('plainSelect');
        $this->assertInstanceOf(Input::class, $input);
        $this->assertInstanceOf(BooleanSelect::class, $bool);
        $this->assertInstanceOf(Select::class, $select);

        // the arrays the two implementations replaced by the helper produced, key order included:
        // resolved fields are read-only, selects carry their options provider before the options, boolean
        // selects only the options, and an unresolved name is a plain input without a noteditable flag
        $expected = [
            'plainInput' => ['name' => 'plainInput', 'title' => $input->getTitle(), 'fieldtype' => 'input', 'noteditable' => true],
            'plainBool' => ['name' => 'plainBool', 'title' => $bool->getTitle(), 'fieldtype' => 'booleanSelect', 'noteditable' => true, 'options' => $bool->getOptions()],
            'plainSelect' => ['name' => 'plainSelect', 'title' => $select->getTitle(), 'fieldtype' => 'select', 'noteditable' => true, 'optionsProviderClass' => $select->getOptionsProviderClass(), 'options' => $select->getOptions()],
            'doesNotExist' => ['name' => 'doesNotExist', 'title' => Pimcore::getContainer()->get('translator')->trans('doesNotExist', [], 'admin'), 'fieldtype' => 'input'],
        ];

        $fd = new ManyToManyObjectRelation();
        $fd->setClasses([['classes' => self::CLASS_NAME]]);
        $fd->setVisibleFields('plainInput,plainBool,plainSelect,doesNotExist');
        $fd->enrichLayoutDefinition(null);
        $this->assertSame($expected, $fd->visibleFieldDefinitions);

        $advanced = new AdvancedManyToManyObjectRelation();
        $advanced->setAllowedClassId(self::CLASS_NAME);
        $advanced->setVisibleFields('plainInput,plainBool,plainSelect,doesNotExist');
        $advanced->enrichLayoutDefinition(null);
        $this->assertSame($expected, $advanced->visibleFieldDefinitions);
    }

    public function testSubclassesOverridingEnrichLayoutDefinitionKeepWorking(): void
    {
        // an override in the style projects wrote against the previous implementations: call the parent, then
        // post-process the definitions
        $decorate = static function (array &$definitions): void {
            foreach ($definitions as &$definition) {
                $definition['custom'] = true;
            }
        };

        $fd = new class($decorate) extends ManyToManyObjectRelation {
            /** @param callable $decorate */
            public function __construct(private $decorate)
            {
            }

            public function enrichLayoutDefinition(?Concrete $object, array $context = []): static
            {
                parent::enrichLayoutDefinition($object, $context);
                ($this->decorate)($this->visibleFieldDefinitions);

                return $this;
            }
        };
        $fd->setClasses([['classes' => self::CLASS_NAME]]);
        $fd->setVisibleFields('plainInput,linput');
        $fd->enrichLayoutDefinition(null);
        $this->assertSame(['plainInput', 'linput'], array_keys($fd->visibleFieldDefinitions));
        $this->assertTrue($fd->visibleFieldDefinitions['plainInput']['custom']);
        $this->assertTrue($fd->visibleFieldDefinitions['linput']['noteditable']);

        $advanced = new class($decorate) extends AdvancedManyToManyObjectRelation {
            /** @param callable $decorate */
            public function __construct(private $decorate)
            {
            }

            public function enrichLayoutDefinition(?Concrete $object, array $context = []): static
            {
                parent::enrichLayoutDefinition($object, $context);
                ($this->decorate)($this->visibleFieldDefinitions);

                return $this;
            }
        };
        $advanced->setAllowedClassId(self::CLASS_NAME);
        $advanced->setVisibleFields('plainInput,linput');
        $advanced->enrichLayoutDefinition(null);
        $this->assertSame(['plainInput', 'linput'], array_keys($advanced->visibleFieldDefinitions));
        $this->assertTrue($advanced->visibleFieldDefinitions['linput']['custom']);
    }

    public function testUnresolvedFieldFallsBackToPlainInput(): void
    {
        $fd = new ManyToManyObjectRelation();
        $fd->setClasses([['classes' => self::CLASS_NAME]]);
        $fd->setVisibleFields('plainInput,doesNotExist');

        $fd->enrichLayoutDefinition(null);

        $definition = $fd->visibleFieldDefinitions['doesNotExist'];
        $this->assertSame('doesNotExist', $definition['name']);
        $this->assertSame('input', $definition['fieldtype']);
        $this->assertIsString($definition['title']);
        $this->assertArrayNotHasKey('noteditable', $definition, 'unresolved names never carried the flag');
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

        // numeric identifiers are looked up as class ids (as string and as int), names by name
        $numericClass = ClassDefinition::getById(self::NUMERIC_CLASS_ID);
        $this->assertInstanceOf(ClassDefinition::class, $numericClass);
        $this->assertSame(self::NUMERIC_CLASS_NAME, $numericClass->getName());
        $this->assertSame(self::NUMERIC_CLASS_ID, VisibleFieldDefinitionHelper::resolveClass(self::NUMERIC_CLASS_ID)?->getId());
        $this->assertSame(self::NUMERIC_CLASS_ID, VisibleFieldDefinitionHelper::resolveClass((int) self::NUMERIC_CLASS_ID)?->getId());
        $this->assertSame(self::NUMERIC_CLASS_ID, VisibleFieldDefinitionHelper::resolveClass(self::NUMERIC_CLASS_NAME)?->getId());
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
        $this->assertSame(['name', 'title', 'fieldtype'], array_keys($fallback));
        $this->assertSame('unknown', $fallback['name']);
        $this->assertSame('input', $fallback['fieldtype']);
    }
}
