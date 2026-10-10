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

use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Db;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreField;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Numeric;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\Vv8846Fc;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Objectbrick\Data\Vv8846BrickA;
use Pimcore\Model\DataObject\Objectbrick\Data\Vv8846BrickB;
use Pimcore\Model\DataObject\Vv8846Inh;
use Pimcore\Model\DataObject\Vv8846Obj;
use Pimcore\Model\Element\StructuredValidationException;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Pimcore\Tool;

/**
 * Structured validation errors (pimcore/pimcore#8846): field, title, translation key and location path
 * of every leaf violation, for plain fields and for all containers.
 *
 * @group model.dataobject.object
 */
class ValidationViolationsTest extends ModelTestCase
{
    private const CLASS_NAME = 'Vv8846Obj';

    private const INHERITING_CLASS_NAME = 'Vv8846Inh';

    private const BRICK_A = 'Vv8846BrickA';

    private const BRICK_B = 'Vv8846BrickB';

    private const FIELDCOLLECTION = 'Vv8846Fc';

    private const STORE_NAME = 'vv8846store';

    private const GROUP_NAME = 'vv8846group';

    private const CS_KEY_NAME = 'vv8846key';

    private const TOO_BIG_NUMBER = 1e20;

    private const TOO_BIG_MESSAGE = 'Value exceeds PHP_INT_MAX please use an input data type instead of numeric!';

    private static int $objectCounter = 0;

    /**
     * Auto-increment values of the classification store tables before this test created its rows. Other tests
     * look up their store config with the default store id 1, so the ids must be handed back.
     *
     * @var array<string, int>
     */
    private static array $autoIncrements = [];

    /**
     * The definitions are created and removed for every test: Codeception runs tearDownAfterClass() only at the
     * end of the whole suite, so class-level fixtures would leak into the tests that follow.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // leftovers of an aborted run, only definitions with the names of this test
        self::removeDefinitions();
        $this->createDefinitions();
    }

    protected function tearDown(): void
    {
        self::removeDefinitions();
        self::restoreAutoIncrements();

        parent::tearDown();
    }

    private static function restoreAutoIncrements(): void
    {
        foreach (self::$autoIncrements as $table => $autoIncrement) {
            // InnoDB keeps the value above the highest id in use, so this cannot clash with other rows
            $db = Db::get();
            $db->executeStatement(
                sprintf('ALTER TABLE %s AUTO_INCREMENT = %d', $db->quoteIdentifier($table), $autoIncrement)
            );
        }
        self::$autoIncrements = [];
        RuntimeCache::clear();
    }

    private static function removeDefinitions(): void
    {
        RuntimeCache::clear();

        foreach ([self::BRICK_A, self::BRICK_B] as $key) {
            Objectbrick\Definition::getByKey($key)?->delete();
        }
        Fieldcollection\Definition::getByKey(self::FIELDCOLLECTION)?->delete();
        ClassDefinition::getByName(self::CLASS_NAME)?->delete();
        ClassDefinition::getByName(self::INHERITING_CLASS_NAME)?->delete();

        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        if ($store) {
            $group = Classificationstore\GroupConfig::getByName(self::GROUP_NAME, $store->getId());
            $key = Classificationstore\KeyConfig::getByName(self::CS_KEY_NAME, $store->getId());
            if ($group && $key) {
                $relation = Classificationstore\KeyGroupRelation::getByGroupAndKeyId($group->getId(), $key->getId());
                $relation?->delete();
            }
            $key?->delete();
            $group?->delete();
            $store->delete();
        }
    }

    public function testMandatoryClassField(): void
    {
        $object = $this->createObject(['plainMandatory' => null]);

        $exception = $this->saveExpectingFailure($object);

        $this->assertStringStartsWith('Validation failed: ', $exception->getMessage());
        $this->assertStringContainsString('[ plainMandatory ]', $exception->getMessage());
        $this->assertSame(
            [['plainMandatory', 'T_plainMandatory', 'validation.mandatory', [], []]],
            $this->describe($exception)
        );
    }

    public function testMandatoryLocalizedField(): void
    {
        $object = $this->createObject(['localized' => false]);

        $exception = $this->saveExpectingFailure($object);

        $this->assertSame(
            'Validation failed: ' . implode(' / ', array_map(
                static fn (string $language) => 'Empty mandatory field [ lMandatory ][ localizedfields-'
                    . $language . ' ]',
                Tool::getRequiredLanguages()
            )),
            $exception->getMessage()
        );

        $expected = [];
        foreach (Tool::getRequiredLanguages() as $language) {
            $expected[] = [
                'lMandatory',
                'T_lMandatory',
                'validation.mandatory',
                [],
                [$this->segment('localizedfields', language: $language)],
            ];
        }
        $this->assertSame($expected, $this->describe($exception));
    }

    public function testMandatoryFieldInObjectBrick(): void
    {
        $object = $this->createObject();
        $brick = new Vv8846BrickA($object);
        $this->fillBrickLocalized($brick);
        $object->getBricks()->setVv8846BrickA($brick);

        $exception = $this->saveExpectingFailure($object);

        $this->assertSame(
            'Validation failed: invalid brick bricks: Empty mandatory field [ bMandatory ][ bricks ]',
            $exception->getMessage()
        );

        $this->assertSame(
            [[
                'bMandatory',
                'T_bMandatory',
                'validation.mandatory',
                [],
                [$this->segment('bricks', 'T_bricks', type: self::BRICK_A, typeTitle: 'T_' . self::BRICK_A)],
            ]],
            $this->describe($exception)
        );
    }

    public function testLocalizedFieldInObjectBrick(): void
    {
        $object = $this->createObject();
        $brick = new Vv8846BrickA($object);
        $brick->setBMandatory('x');
        $object->getBricks()->setVv8846BrickA($brick);

        $exception = $this->saveExpectingFailure($object);

        $expected = [];
        foreach (Tool::getRequiredLanguages() as $language) {
            $expected[] = [
                'lbMandatory',
                'T_lbMandatory',
                'validation.mandatory',
                [],
                [
                    $this->segment('localizedfields', language: $language),
                    $this->segment('bricks', 'T_bricks', type: self::BRICK_A, typeTitle: 'T_' . self::BRICK_A),
                ],
            ];
        }
        $this->assertSame($expected, $this->describe($exception));
    }

    public function testMandatoryFieldInFieldCollectionItem(): void
    {
        $object = $this->createObject();
        $valid = new Vv8846Fc();
        $valid->setFcMandatory('x');
        $invalid = new Vv8846Fc();
        $object->setFc(new Fieldcollection([$valid, $invalid], 'fc'));

        $exception = $this->saveExpectingFailure($object);

        $this->assertSame(
            'Validation failed: Empty mandatory field [ fcMandatory ][ fc-1 ]',
            $exception->getMessage()
        );

        $this->assertSame(
            [[
                'fcMandatory',
                'T_fcMandatory',
                'validation.mandatory',
                [],
                [$this->segment(
                    'fc',
                    'T_fc',
                    index: 1,
                    type: self::FIELDCOLLECTION,
                    typeTitle: 'T_' . self::FIELDCOLLECTION
                )],
            ]],
            $this->describe($exception)
        );
    }

    public function testMandatoryFieldInBlockItem(): void
    {
        $object = $this->createObject();
        $object->setBlock([
            ['bkMandatory' => new BlockElement('bkMandatory', 'input', 'x')],
            ['bkMandatory' => new BlockElement('bkMandatory', 'input', null)],
        ]);

        $exception = $this->saveExpectingFailure($object);

        $this->assertSame(
            [[
                'bkMandatory',
                'T_bkMandatory',
                'validation.mandatory',
                [],
                [$this->segment('block', 'T_block', index: 1)],
            ]],
            $this->describe($exception)
        );
    }

    public function testMaxItemsOfObjectBricks(): void
    {
        $object = $this->createObject();
        $brickA = new Vv8846BrickA($object);
        $this->fillBrickLocalized($brickA);
        $brickA->setBMandatory('x');
        $brickB = new Vv8846BrickB($object);
        $object->getBricks()->setVv8846BrickA($brickA);
        $object->getBricks()->setVv8846BrickB($brickB);

        $exception = $this->saveExpectingFailure($object);

        $this->assertSame(
            [['bricks', 'T_bricks', 'validation.max_items', ['max' => 1], []]],
            $this->describe($exception)
        );
    }

    public function testMaxItemsOfFieldCollection(): void
    {
        $object = $this->createObject();
        $items = [];
        for ($i = 0; $i < 3; $i++) {
            $item = new Vv8846Fc();
            $item->setFcMandatory('x');
            $items[] = $item;
        }
        $object->setFc(new Fieldcollection($items, 'fc'));

        $exception = $this->saveExpectingFailure($object);

        $this->assertSame(
            [['fc', 'T_fc', 'validation.max_items', ['max' => 2], []]],
            $this->describe($exception)
        );
    }

    public function testMandatoryClassificationstoreKey(): void
    {
        $object = $this->createObject();
        $store = Classificationstore\StoreConfig::getByName(self::STORE_NAME);
        $group = Classificationstore\GroupConfig::getByName(self::GROUP_NAME, $store->getId());
        $object->getCs()->setActiveGroups([$group->getId() => true]);

        $exception = $this->saveExpectingFailure($object);

        $languages = array_merge(['default'], Tool::getValidLanguages());
        $messages = array_map(
            static fn (string $language) => 'Empty mandatory field [ vv8846key ] (' . $language . ')',
            $languages
        );
        $this->assertSame(
            'Validation failed: ' . implode(', ', $messages) . ' ('
                . implode(', ', array_map(static fn (string $message) => $message . '[ cs ][ cs ]', $messages)) . ')',
            $exception->getMessage()
        );

        $expected = [];
        foreach (array_merge(['default'], Tool::getValidLanguages()) as $language) {
            $expected[] = [
                self::CS_KEY_NAME,
                'T_' . self::CS_KEY_NAME,
                'validation.mandatory',
                [],
                [
                    $this->segment(self::GROUP_NAME, 'T_' . self::GROUP_NAME),
                    $this->segment('cs', 'T_cs', language: $language === 'default' ? null : $language),
                ],
            ];
        }
        $this->assertSame($expected, $this->describe($exception));
    }

    public function testViolationsReflectListenerModifiedExceptions(): void
    {
        $object = $this->createObject(['plainMandatory' => null, 'plainMandatory2' => null]);

        $listener = static function (DataObjectEvent $event): void {
            $event->setArgument('validationExceptions', [$event->getArgument('validationExceptions')[1]]);
        };
        $dispatcher = Pimcore::getEventDispatcher();
        $dispatcher->addListener(DataObjectEvents::PRE_UPDATE_VALIDATION_EXCEPTION, $listener);

        try {
            $exception = $this->saveExpectingFailure($object);
        } finally {
            $dispatcher->removeListener(DataObjectEvents::PRE_UPDATE_VALIDATION_EXCEPTION, $listener);
        }

        $this->assertStringNotContainsString('[ plainMandatory ]', $exception->getMessage());
        $this->assertStringContainsString('[ plainMandatory2 ]', $exception->getMessage());
        $this->assertSame(
            [['plainMandatory2', 'T_plainMandatory2', 'validation.mandatory', [], []]],
            $this->describe($exception)
        );
    }

    /**
     * A data type that throws a plain ValidationException (e.g. a custom data type) inside a container: the
     * container converts it, so it still becomes a violation with field and path, and the message is unchanged.
     */
    public function testPlainValidationExceptionInContainer(): void
    {
        $language = Tool::getRequiredLanguages()[0];
        $object = $this->createObject();
        $object->setLNumeric(self::TOO_BIG_NUMBER, $language);

        $exception = $this->saveExpectingFailure($object);

        $this->assertSame(
            'Validation failed: ' . self::TOO_BIG_MESSAGE . '[ localizedfields-' . $language . ' ]',
            $exception->getMessage()
        );
        $this->assertSame(
            [['lNumeric', 'T_lNumeric', null, [], [$this->segment('localizedfields', language: $language)]]],
            $this->describe($exception)
        );

        $original = $exception->getViolations()[0]->getPrevious();
        $this->assertSame(ValidationException::class, $original::class);
        $this->assertSame(self::TOO_BIG_MESSAGE, $original->getMessage());
    }

    /**
     * Listeners of PRE_UPDATE_VALIDATION_EXCEPTION get the exceptions exactly as thrown by the data types,
     * plain ones are converted only for the violations of the final exception.
     */
    public function testListenerReceivesOriginalExceptions(): void
    {
        $object = $this->createObject(['plainMandatory' => null]);
        $object->setPlainNumeric(self::TOO_BIG_NUMBER);

        $received = [];
        $listener = static function (DataObjectEvent $event) use (&$received): void {
            $received = $event->getArgument('validationExceptions');
        };
        $dispatcher = Pimcore::getEventDispatcher();
        $dispatcher->addListener(DataObjectEvents::PRE_UPDATE_VALIDATION_EXCEPTION, $listener);

        try {
            $exception = $this->saveExpectingFailure($object);
        } finally {
            $dispatcher->removeListener(DataObjectEvents::PRE_UPDATE_VALIDATION_EXCEPTION, $listener);
        }

        $this->assertCount(2, $received);
        $violations = $exception->getViolations();
        $this->assertSame($received[0], $violations[0], 'a structured exception is passed on as is');
        $this->assertSame(ValidationException::class, $received[1]::class, 'a plain exception is not converted');
        $this->assertSame($received[1], $violations[1]->getPrevious());
        $this->assertSame(
            [
                ['plainMandatory', 'T_plainMandatory', 'validation.mandatory', [], []],
                ['plainNumeric', 'T_plainNumeric', null, [], []],
            ],
            $this->describe($exception)
        );
        $this->assertSame(
            'Validation failed: Empty mandatory field [ plainMandatory ] / ' . self::TOO_BIG_MESSAGE,
            $exception->getMessage()
        );
    }

    /**
     * Inheritance makes Concrete::validate() retry with the parent's value and rebuild the exception
     * via withMessage(): translation, field and path must survive, the message gets the fieldname suffix.
     */
    public function testInheritanceRetryKeepsStructuredDataOfClassField(): void
    {
        $exception = $this->saveInheritingChildExpectingFailure(false);

        $this->assertSame(
            'Validation failed: Empty mandatory field [ inhPlain ] fieldname=inhPlain',
            $exception->getMessage()
        );
        $this->assertSame(
            [['inhPlain', 'T_inhPlain', 'validation.mandatory', [], []]],
            $this->describe($exception)
        );
        $this->assertStringEndsWith(' fieldname=inhPlain', $exception->getViolations()[0]->getMessage());
    }

    /**
     * Same for the retry in Localizedfields::checkValidity().
     */
    public function testInheritanceRetryKeepsStructuredDataOfLocalizedField(): void
    {
        $exception = $this->saveInheritingChildExpectingFailure(true);

        $expected = [];
        foreach (Tool::getRequiredLanguages() as $language) {
            $expected[] = [
                'inhLocal',
                'T_inhLocal',
                'validation.mandatory',
                [],
                [$this->segment('localizedfields', language: $language)],
            ];
        }
        $this->assertSame($expected, $this->describe($exception));
        $this->assertSame(
            'Validation failed: ' . implode(' / ', array_map(
                static fn (string $language) => 'Empty mandatory field [ inhLocal ] fieldname=inhLocal'
                    . '[ localizedfields-' . $language . ' ]',
                Tool::getRequiredLanguages()
            )),
            $exception->getMessage()
        );
        foreach ($exception->getViolations() as $violation) {
            $this->assertStringEndsWith(' fieldname=inhLocal', $violation->getMessage());
        }
    }

    private function saveInheritingChildExpectingFailure(bool $plainFilled): StructuredValidationException
    {
        $inheritedValues = DataObject::doGetInheritedValues();

        try {
            $parent = new Vv8846Inh();
            $parent->setParentId(1);
            $parent->setKey('vv8846-parent-' . ++self::$objectCounter . '-' . uniqid());
            $parent->setPublished(true);
            $parent->setOmitMandatoryCheck(true);
            $parent->save();

            $child = new Vv8846Inh();
            $child->setParentId($parent->getId());
            $child->setKey('vv8846-child-' . ++self::$objectCounter . '-' . uniqid());
            $child->setPublished(true);
            if ($plainFilled) {
                $child->setInhPlain('x');
            } else {
                foreach (Tool::getRequiredLanguages() as $language) {
                    $child->setInhLocal('x', $language);
                }
            }

            return $this->saveExpectingFailure($child);
        } finally {
            // the validation retry switches inheritance on and does not switch it off when it fails
            DataObject::setGetInheritedValues($inheritedValues);
        }
    }

    /**
     * @return list<array{string|null, string|null, string|null, array<string, mixed>, list<array<string, mixed>>}>
     */
    private function describe(StructuredValidationException $exception): array
    {
        $result = [];
        foreach ($exception->getViolations() as $violation) {
            $result[] = [
                $violation->getFieldName(),
                $violation->getFieldTitle(),
                $violation->getTranslationKey(),
                $violation->getTranslationParameters(),
                array_map(
                    static fn ($segment) => $segment->toArray(),
                    $violation->getPath()
                ),
            ];
        }

        return $result;
    }

    /**
     * @return array{
     *     field: string,
     *     title: ?string,
     *     language: ?string,
     *     index: ?int,
     *     type: ?string,
     *     typeTitle: ?string
     * }
     */
    private function segment(
        string $field,
        ?string $title = null,
        ?string $language = null,
        ?int $index = null,
        ?string $type = null,
        ?string $typeTitle = null
    ): array {
        return [
            'field' => $field,
            'title' => $title,
            'language' => $language,
            'index' => $index,
            'type' => $type,
            'typeTitle' => $typeTitle,
        ];
    }

    private function saveExpectingFailure(Concrete $object): StructuredValidationException
    {
        try {
            $object->save();
        } catch (StructuredValidationException $exception) {
            return $exception;
        }

        $this->fail('Expected a StructuredValidationException');
    }

    /**
     * Creates an unsaved object whose mandatory fields are all filled, except those switched off in $options.
     *
     * @param array<string, mixed> $options
     */
    private function createObject(array $options = []): Vv8846Obj
    {
        $object = new Vv8846Obj();
        $object->setParentId(1);
        $object->setKey('vv8846-' . ++self::$objectCounter . '-' . uniqid());
        $object->setPublished(true);

        if (!array_key_exists('plainMandatory', $options)) {
            $object->setPlainMandatory('x');
        }
        if (!array_key_exists('plainMandatory2', $options)) {
            $object->setPlainMandatory2('x');
        }
        if (!array_key_exists('localized', $options)) {
            foreach (Tool::getRequiredLanguages() as $language) {
                $object->setLMandatory('x', $language);
            }
        }

        return $object;
    }

    private function fillBrickLocalized(Vv8846BrickA $brick): void
    {
        foreach (Tool::getRequiredLanguages() as $language) {
            $brick->getLocalizedfields()->setLocalizedValue('lbMandatory', 'x', $language);
        }
    }

    private function input(string $name, bool $mandatory): Input
    {
        $input = new Input();
        $input->setName($name);
        $input->setTitle('T_' . $name);
        $input->setMandatory($mandatory);

        return $input;
    }

    private function numeric(string $name): Numeric
    {
        $numeric = new Numeric();
        $numeric->setName($name);
        $numeric->setTitle('T_' . $name);

        return $numeric;
    }

    private function localizedfields(string $name, Data ...$children): Localizedfields
    {
        $localizedfields = new Localizedfields();
        $localizedfields->setName('localizedfields');
        $localizedfields->setChildren([$this->input($name, true), ...$children]);

        return $localizedfields;
    }

    private function panel(array $children): Panel
    {
        $panel = new Panel();
        $panel->setName('layout');
        $panel->setChildren($children);

        return $panel;
    }

    private function createDefinitions(): void
    {
        $store = new Classificationstore\StoreConfig();
        $store->setName(self::STORE_NAME);
        $store->save();
        self::$autoIncrements['classificationstore_stores'] = $store->getId();

        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($store->getId());
        $group->setName(self::GROUP_NAME);
        $group->setDescription('T_' . self::GROUP_NAME);
        $group->save();
        self::$autoIncrements['classificationstore_groups'] = $group->getId();

        $key = new Classificationstore\KeyConfig();
        $key->setStoreId($store->getId());
        $key->setName(self::CS_KEY_NAME);
        $key->setEnabled(true);
        $key->setType('input');
        $key->setDefinition((string) json_encode($this->input(self::CS_KEY_NAME, false)));
        $key->save();
        self::$autoIncrements['classificationstore_keys'] = $key->getId();

        $relation = new Classificationstore\KeyGroupRelation();
        $relation->setGroupId($group->getId());
        $relation->setKeyId($key->getId());
        $relation->setSorter(1);
        $relation->setMandatory(true);
        $relation->save();

        $bricks = new Objectbricks();
        $bricks->setName('bricks');
        $bricks->setTitle('T_bricks');
        $bricks->setMaxItems(1);

        $fc = new Fieldcollections();
        $fc->setName('fc');
        $fc->setTitle('T_fc');
        $fc->setMaxItems(2);

        $block = new Block();
        $block->setName('block');
        $block->setTitle('T_block');
        $block->setChildren([$this->input('bkMandatory', true)]);

        $cs = new ClassificationstoreField();
        $cs->setName('cs');
        $cs->setTitle('T_cs');
        $cs->setStoreId($store->getId());
        $cs->setLocalized(true);

        $class = new ClassDefinition();
        $class->setName(self::CLASS_NAME);
        $class->setId(self::CLASS_NAME);
        $class->setUserOwner(1);
        $class->setLayoutDefinitions($this->panel([
            $this->input('plainMandatory', true),
            $this->input('plainMandatory2', true),
            $this->numeric('plainNumeric'),
            $this->localizedfields('lMandatory', $this->numeric('lNumeric')),
            $bricks,
            $fc,
            $block,
            $cs,
        ]));
        $class->save();

        $brickLayout = $this->panel([$this->input('bMandatory', true), $this->localizedfields('lbMandatory')]);
        $this->createBrick(self::BRICK_A, $brickLayout);
        $this->createBrick(self::BRICK_B, $this->panel([$this->input('bOptional', false)]));

        $fcDefinition = new Fieldcollection\Definition();
        $fcDefinition->setKey(self::FIELDCOLLECTION);
        $fcDefinition->setTitle('T_' . self::FIELDCOLLECTION);
        $fcDefinition->setLayoutDefinitions($this->panel([$this->input('fcMandatory', true)]));
        $fcDefinition->save();

        $class = ClassDefinition::getByName(self::CLASS_NAME);
        $class->getFieldDefinition('bricks')->setAllowedTypes([self::BRICK_A, self::BRICK_B]);
        $class->getFieldDefinition('fc')->setAllowedTypes([self::FIELDCOLLECTION]);
        $class->save();

        $inheriting = new ClassDefinition();
        $inheriting->setName(self::INHERITING_CLASS_NAME);
        $inheriting->setId(self::INHERITING_CLASS_NAME);
        $inheriting->setUserOwner(1);
        $inheriting->setAllowInherit(true);
        $inheriting->setLayoutDefinitions($this->panel([
            $this->input('inhPlain', true),
            $this->localizedfields('inhLocal'),
        ]));
        $inheriting->save();

        RuntimeCache::clear();
    }

    private function createBrick(string $key, Panel $layout): void
    {
        $brick = new Objectbrick\Definition();
        $brick->setKey($key);
        $brick->setTitle('T_' . $key);
        $brick->setLayoutDefinitions($layout);
        $brick->setClassDefinitions([['classname' => self::CLASS_NAME, 'fieldname' => 'bricks']]);
        $brick->save();
    }
}
