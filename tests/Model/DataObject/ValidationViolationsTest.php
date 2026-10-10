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
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreField;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Fieldcollection\Data\Vv8846Fc;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Objectbrick\Data\Vv8846BrickA;
use Pimcore\Model\DataObject\Objectbrick\Data\Vv8846BrickB;
use Pimcore\Model\DataObject\Vv8846Obj;
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

    private const BRICK_A = 'Vv8846BrickA';

    private const BRICK_B = 'Vv8846BrickB';

    private const FIELDCOLLECTION = 'Vv8846Fc';

    private const STORE_NAME = 'vv8846store';

    private const GROUP_NAME = 'vv8846group';

    private const CS_KEY_NAME = 'vv8846key';

    private static int $objectCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $complete = ClassDefinition::getByName(self::CLASS_NAME)
            && Fieldcollection\Definition::getByKey(self::FIELDCOLLECTION);
        if (!$complete) {
            self::removeDefinitions();
            $this->createDefinitions();
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::removeDefinitions();

        parent::tearDownAfterClass();
    }

    private static function removeDefinitions(): void
    {
        RuntimeCache::clear();

        foreach ([self::BRICK_A, self::BRICK_B] as $key) {
            Objectbrick\Definition::getByKey($key)?->delete();
        }
        Fieldcollection\Definition::getByKey(self::FIELDCOLLECTION)?->delete();
        ClassDefinition::getByName(self::CLASS_NAME)?->delete();

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
            [[
                'bMandatory',
                'T_bMandatory',
                'validation.mandatory',
                [],
                [$this->segment('bricks', 'T_bricks', type: self::BRICK_A)],
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
                    $this->segment('bricks', 'T_bricks', type: self::BRICK_A),
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
            [[
                'fcMandatory',
                'T_fcMandatory',
                'validation.mandatory',
                [],
                [$this->segment('fc', 'T_fc', index: 1, type: self::FIELDCOLLECTION)],
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

        $expected = [];
        foreach (array_merge(['default'], Tool::getValidLanguages()) as $language) {
            $expected[] = [
                self::CS_KEY_NAME,
                'T_' . self::CS_KEY_NAME,
                'validation.mandatory',
                [],
                [
                    $this->segment(self::GROUP_NAME),
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
     * @return list<array{string|null, string|null, string|null, array<string, mixed>, list<array<string, mixed>>}>
     */
    private function describe(ValidationException $exception): array
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
     * @return array{field: string, title: ?string, language: ?string, index: ?int, type: ?string}
     */
    private function segment(
        string $field,
        ?string $title = null,
        ?string $language = null,
        ?int $index = null,
        ?string $type = null
    ): array {
        return [
            'field' => $field,
            'title' => $title,
            'language' => $language,
            'index' => $index,
            'type' => $type,
        ];
    }

    private function saveExpectingFailure(Vv8846Obj $object): ValidationException
    {
        try {
            $object->save();
        } catch (ValidationException $exception) {
            return $exception;
        }

        $this->fail('Expected a ValidationException');
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

    private function localizedfields(string $name): Localizedfields
    {
        $localizedfields = new Localizedfields();
        $localizedfields->setName('localizedfields');
        $localizedfields->setChildren([$this->input($name, true)]);

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

        $group = new Classificationstore\GroupConfig();
        $group->setStoreId($store->getId());
        $group->setName(self::GROUP_NAME);
        $group->save();

        $key = new Classificationstore\KeyConfig();
        $key->setStoreId($store->getId());
        $key->setName(self::CS_KEY_NAME);
        $key->setEnabled(true);
        $key->setType('input');
        $key->setDefinition((string) json_encode($this->input(self::CS_KEY_NAME, false)));
        $key->save();

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
            $this->localizedfields('lMandatory'),
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
        $fcDefinition->setLayoutDefinitions($this->panel([$this->input('fcMandatory', true)]));
        $fcDefinition->save();

        $class = ClassDefinition::getByName(self::CLASS_NAME);
        $class->getFieldDefinition('bricks')->setAllowedTypes([self::BRICK_A, self::BRICK_B]);
        $class->getFieldDefinition('fc')->setAllowedTypes([self::FIELDCOLLECTION]);
        $class->save();

        RuntimeCache::clear();
    }

    private function createBrick(string $key, Panel $layout): void
    {
        $brick = new Objectbrick\Definition();
        $brick->setKey($key);
        $brick->setLayoutDefinitions($layout);
        $brick->setClassDefinitions([['classname' => self::CLASS_NAME, 'fieldname' => 'bricks']]);
        $brick->save();
    }
}
