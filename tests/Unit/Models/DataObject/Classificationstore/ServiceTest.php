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

namespace Pimcore\Tests\Unit\Model\DataObject\Classificationstore;

use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\MultiValue;
use Pimcore\Element\MarshallerService;
use Pimcore\DataObject\ClassificationstoreDataMarshaller\MultiValue as MultiValueMarshaller;
use Pimcore;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore as ClassificationstoreField;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Geopoint;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\Service;
use Pimcore\Model\DataObject\Fieldcollection\Definition as FieldcollectionDefinition;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Pimcore\Tests\Support\Test\TestCase;

class ServiceTest extends TestCase
{
    private const BRICK_KEY = 'csEngineBrick';

    private const OTHER_BRICK_KEY = 'csEngineOtherBrick';

    /**
     * Object bricks and field collections are keyed in separate name spaces, so one key can name
     * both - which is exactly the case the qualified identity has to keep apart.
     */
    private const SHARED_KEY = 'csEngineShared';

    private const KEY_ID = 7;

    private const GROUP_A = 11;

    private const GROUP_B = 12;

    private const TITLE_IN_GROUP_A = 'In group A';

    /**
     * A second title, carried by a relation that no group is asking for.
     */
    private const OTHER_TITLE = 'Another title';

    protected function setUp(): void
    {
        parent::setUp();

        // the definitions cache outlives a single test, so it is emptied before every one of them
        Service::clearDefinitionsCache();
    }

    protected function tearDown(): void
    {
        Service::clearDefinitionsCache();

        foreach ([self::BRICK_KEY, self::OTHER_BRICK_KEY, self::SHARED_KEY] as $brickKey) {
            RuntimeCache::getInstance()->offsetUnset('objectbrick_' . $brickKey);
        }

        RuntimeCache::getInstance()->offsetUnset('fieldcollection_' . self::SHARED_KEY);

        parent::tearDown();
    }

    /**
     * The pre-validation pass and classSaved() have to agree on which fields are classification
     * store fields: classSaved() is dispatched into localized fields as well, so the collector the
     * validation uses must reach the same ones.
     */
    public function testTheCollectorFindsClassificationStoreFieldsInsideLocalizedFields(): void
    {
        $localized = new Localizedfields();
        $localized->setName('localizedfields');
        $localized->setChildren([$this->classificationStoreField('csLocalized'), new Input()]);

        $fieldDefinitions = [
            'plain' => new Input(),
            'csTopLevel' => $this->classificationStoreField('csTopLevel'),
            'localizedfields' => $localized,
        ];

        $this->assertSame(
            ['csTopLevel', 'csLocalized'],
            array_keys(Service::getClassificationStoreFieldDefinitions($fieldDefinitions))
        );
    }

    /**
     * Objectbricks dispatch classSaved() with the owning class as well, so a classification store
     * inside a brick has to be reachable from the class's own field definitions.
     */
    public function testTheCollectorFindsClassificationStoreFieldsInsideObjectBricks(): void
    {
        $this->registerBrick($this->classificationStoreField('csInBrick', 5));

        $bricks = new Objectbricks();
        $bricks->setName('bricks');
        $bricks->setAllowedTypes([self::BRICK_KEY]);

        $collected = Service::getClassificationStoreFieldDefinitions(['bricks' => $bricks]);

        $this->assertSame(['objectbrick:' . self::BRICK_KEY . '.csInBrick'], array_keys($collected));
        $this->assertSame(5, Service::getConfiguredStoreId($collected['objectbrick:' . self::BRICK_KEY . '.csInBrick']));
    }

    /**
     * Two bricks allowed on the same field may each define a classification store under the same
     * name, on different stores. Keying the collection by name alone dropped the second one, and
     * with it the engine validation of the store behind it.
     */
    public function testTwoBricksDefiningTheSameFieldNameAreBothCollected(): void
    {
        $this->registerBrick($this->classificationStoreField('cs', 5));
        $this->registerBrick($this->classificationStoreField('cs', 6), self::OTHER_BRICK_KEY);

        $bricks = new Objectbricks();
        $bricks->setName('bricks');
        $bricks->setAllowedTypes([self::BRICK_KEY, self::OTHER_BRICK_KEY]);

        $collected = Service::getClassificationStoreFieldDefinitions(['bricks' => $bricks]);

        $this->assertSame(
            ['objectbrick:' . self::BRICK_KEY . '.cs', 'objectbrick:' . self::OTHER_BRICK_KEY . '.cs'],
            array_keys($collected)
        );
        $this->assertSame(5, Service::getConfiguredStoreId($collected['objectbrick:' . self::BRICK_KEY . '.cs']));
        $this->assertSame(6, Service::getConfiguredStoreId($collected['objectbrick:' . self::OTHER_BRICK_KEY . '.cs']));
    }

    /**
     * Object brick keys and field collection keys live in separate name spaces, so a brick and a
     * field collection may both be called the same thing and both contain a "cs". Qualifying the
     * identity by key alone gave them one and the same key, which dropped one of the two
     * definitions - and with it the engine of the store behind it.
     */
    public function testABrickAndAFieldCollectionSharingAKeyAreBothCollected(): void
    {
        $this->registerBrick($this->classificationStoreField('cs', 5), self::SHARED_KEY);
        $this->registerFieldCollection($this->classificationStoreField('cs', 6), self::SHARED_KEY);

        $bricks = new Objectbricks();
        $bricks->setName('bricks');
        $bricks->setAllowedTypes([self::SHARED_KEY]);

        $fieldCollections = new Fieldcollections();
        $fieldCollections->setName('fieldcollections');
        $fieldCollections->setAllowedTypes([self::SHARED_KEY]);

        $collected = Service::getClassificationStoreFieldDefinitions([
            'bricks' => $bricks,
            'fieldcollections' => $fieldCollections,
        ]);

        $this->assertSame(
            ['objectbrick:' . self::SHARED_KEY . '.cs', 'fieldcollection:' . self::SHARED_KEY . '.cs'],
            array_keys($collected)
        );
        $this->assertSame(5, Service::getConfiguredStoreId($collected['objectbrick:' . self::SHARED_KEY . '.cs']));
        $this->assertSame(6, Service::getConfiguredStoreId($collected['fieldcollection:' . self::SHARED_KEY . '.cs']));
    }

    /**
     * Both definitions have to reach the group the engine is resolved from, or the store of the one
     * that was dropped is never checked against the other's.
     */
    public function testTheStoresOfABrickAndAFieldCollectionSharingAKeyAreBothGroupedUnderTheFieldName(): void
    {
        $this->registerBrick($this->classificationStoreField('cs', 5), self::SHARED_KEY);
        $this->registerFieldCollection($this->classificationStoreField('cs', 6), self::SHARED_KEY);

        $bricks = new Objectbricks();
        $bricks->setName('bricks');
        $bricks->setAllowedTypes([self::SHARED_KEY]);

        $fieldCollections = new Fieldcollections();
        $fieldCollections->setName('fieldcollections');
        $fieldCollections->setAllowedTypes([self::SHARED_KEY]);

        $this->assertSame(
            [
                'cs' => [
                    'objectbrick:' . self::SHARED_KEY . '.cs' => 5,
                    'fieldcollection:' . self::SHARED_KEY . '.cs' => 6,
                ],
            ],
            Service::groupStoreIdsByFieldName(Service::getClassificationStoreFieldDefinitions([
                'bricks' => $bricks,
                'fieldcollections' => $fieldCollections,
            ]))
        );
    }

    /**
     * The value tables key their rows by (object, fieldname), so the definitions sharing a name are
     * what has to be resolved together.
     */
    public function testTheStoresOfTheDefinitionsSharingAFieldNameAreGroupedUnderIt(): void
    {
        $this->registerBrick($this->classificationStoreField('cs', 5));
        $this->registerBrick($this->classificationStoreField('cs', 6), self::OTHER_BRICK_KEY);

        $bricks = new Objectbricks();
        $bricks->setName('bricks');
        $bricks->setAllowedTypes([self::BRICK_KEY, self::OTHER_BRICK_KEY]);

        $this->assertSame(
            [
                'other' => ['other' => null],
                'cs' => [
                    'objectbrick:' . self::BRICK_KEY . '.cs' => 5,
                    'objectbrick:' . self::OTHER_BRICK_KEY . '.cs' => 6,
                ],
            ],
            Service::groupStoreIdsByFieldName(Service::getClassificationStoreFieldDefinitions([
                'other' => $this->classificationStoreField('other'),
                'bricks' => $bricks,
            ]))
        );
    }

    public function testAClassWithoutClassificationStoreFieldsCollectsNothing(): void
    {
        $this->assertSame([], Service::getClassificationStoreFieldDefinitions(['plain' => new Input()]));
    }

    /**
     * storeId is a typed property without a default, so an uninitialised one must be reported as
     * "no store" rather than raise an Error or be mistaken for store 1.
     */
    public function testTheConfiguredStoreIdIsNullWhenTheFieldCarriesNone(): void
    {
        $this->assertNull(Service::getConfiguredStoreId($this->classificationStoreField('cs')));
    }

    public function testTheConfiguredStoreIdIsTheStoreOfTheField(): void
    {
        $this->assertSame(4, Service::getConfiguredStoreId($this->classificationStoreField('cs', 4)));
    }

    /**
     * The same key assigned to two groups carries its own assignment in each of them, so the
     * definition of one group must not be served from the entry of another.
     */
    public function testTwoGroupsOfOneKeyEachGetTheDefinitionOfTheirOwnRelation(): void
    {
        $first = Service::getFieldDefinitionFromKeyConfig($this->relation(self::GROUP_A, self::TITLE_IN_GROUP_A));
        $second = Service::getFieldDefinitionFromKeyConfig($this->relation(self::GROUP_B, 'In group B'));

        $this->assertNotSame($first, $second);
        $this->assertSame(self::TITLE_IN_GROUP_A, $first->getTitle());
        $this->assertSame('In group B', $second->getTitle());
    }

    /**
     * What distinguishes the per group entry from dropping the memoization for relations: one
     * definition is still built per (groupId, keyId), not one per call.
     */
    public function testTwoRelationsOfTheSameGroupAndKeyShareOneDefinition(): void
    {
        $this->assertSame(
            Service::getFieldDefinitionFromKeyConfig($this->relation(self::GROUP_A, self::TITLE_IN_GROUP_A)),
            Service::getFieldDefinitionFromKeyConfig($this->relation(self::GROUP_A, self::TITLE_IN_GROUP_A))
        );
    }

    public function testAKeyConfigIsStillKeyedByItsIdAlone(): void
    {
        $keyConfig = $this->keyConfig('The key');

        $this->assertSame(
            Service::getFieldDefinitionFromKeyConfig($keyConfig),
            Service::getFieldDefinitionFromKeyConfig($keyConfig)
        );
    }

    /**
     * The per group entry is deliberately not numeric: a numeric string array key is coerced to an
     * int, which would make it the entry of the key id itself.
     */
    public function testAKeyConfigAndARelationOfTheSameKeyIdDoNotShareAnEntry(): void
    {
        $keyConfig = $this->keyConfig('The key');
        $fromKeyConfig = Service::getFieldDefinitionFromKeyConfig($keyConfig);

        $relation = $this->relation(self::GROUP_A, self::TITLE_IN_GROUP_A);
        $fromRelation = Service::getFieldDefinitionFromKeyConfig($relation);

        $this->assertNotSame($fromKeyConfig, $fromRelation);
        $this->assertSame(self::TITLE_IN_GROUP_A, $fromRelation->getTitle());
        $this->assertSame($fromKeyConfig, Service::getFieldDefinitionFromKeyConfig($keyConfig));
        $this->assertSame('The key', $fromKeyConfig->getTitle());
    }

    /**
     * KeyConfig::getId() is nullable and PHP turns a null array key into '', so memoizing a key
     * config that was never saved handed every other unsaved one the first one's definition.
     */
    public function testTwoUnsavedKeyConfigsEachGetTheirOwnDefinition(): void
    {
        $first = Service::getFieldDefinitionFromKeyConfig($this->unsavedKeyConfig('The first key'));
        $second = Service::getFieldDefinitionFromKeyConfig($this->unsavedKeyConfig('The second key'));

        $this->assertNotSame($first, $second);
        $this->assertSame('The first key', $first->getTitle());
        $this->assertSame('The second key', $second->getTitle());
    }

    /**
     * An unsaved key config is identified by nothing, so it is not memoized at all - and leaves no
     * entry behind that the key it is saved as would then be served from.
     */
    public function testAnUnsavedKeyConfigIsNotMemoized(): void
    {
        $keyConfig = $this->unsavedKeyConfig('The key');

        $this->assertNotSame(
            Service::getFieldDefinitionFromKeyConfig($keyConfig),
            Service::getFieldDefinitionFromKeyConfig($keyConfig)
        );
    }

    /**
     * The per group entry is built from two typed properties that have no default, so a relation
     * carrying neither has to leave it unresolved - reading them raised an Error on the path every
     * definition resolution goes through, and nothing identifies such a relation to memoize it under.
     */
    public function testARelationCarryingNeitherIdResolvesItsDefinitionAndIsNotMemoized(): void
    {
        $first = Service::getFieldDefinitionFromKeyConfig($this->relationWithoutIds(self::TITLE_IN_GROUP_A));
        $second = Service::getFieldDefinitionFromKeyConfig($this->relationWithoutIds(self::OTHER_TITLE));

        $this->assertNotSame($first, $second);
        $this->assertSame(self::TITLE_IN_GROUP_A, $first->getTitle());
        $this->assertSame(self::OTHER_TITLE, $second->getTitle());
    }

    /**
     * Half the entry is no entry: one of the two ids identifies a relation no better than neither
     * does, and reading the one that is missing raises the same Error.
     */
    public function testARelationCarryingOnlyOneOfTheTwoIdsIsNotMemoized(): void
    {
        $withoutGroupId = $this->relationWithoutIds(self::TITLE_IN_GROUP_A);
        $withoutGroupId->setKeyId(self::KEY_ID);

        $withoutKeyId = $this->relationWithoutIds(self::OTHER_TITLE);
        $withoutKeyId->setGroupId(self::GROUP_A);

        $this->assertNotSame(
            Service::getFieldDefinitionFromKeyConfig($withoutGroupId),
            Service::getFieldDefinitionFromKeyConfig($withoutGroupId)
        );
        $this->assertNotSame(
            Service::getFieldDefinitionFromKeyConfig($withoutKeyId),
            Service::getFieldDefinitionFromKeyConfig($withoutKeyId)
        );
    }

    public function testClearingTheDefinitionsCacheEmptiesThePerGroupEntriesAsWell(): void
    {
        $first = Service::getFieldDefinitionFromKeyConfig($this->relation(self::GROUP_A, self::TITLE_IN_GROUP_A));

        Service::clearDefinitionsCache();

        $this->assertNotSame(
            $first,
            Service::getFieldDefinitionFromKeyConfig($this->relation(self::GROUP_A, self::TITLE_IN_GROUP_A))
        );
    }

    /**
     * A relation's definition is the key's own joined column, so every group of a key answers the
     * same JSON today - the differing title is set explicitly to make a leaked entry observable.
     */
    private function relation(int $groupId, string $title): KeyGroupRelation
    {
        $relation = $this->relationWithoutIds($title);
        $relation->setGroupId($groupId);
        $relation->setKeyId(self::KEY_ID);

        return $relation;
    }

    /**
     * A relation as it is built with `new`: keyId and groupId are typed properties without a default,
     * so neither answers anything until it is set.
     */
    private function relationWithoutIds(string $title): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setType('input');
        $relation->setDefinition(json_encode(['name' => 'csKey', 'title' => $title]));

        return $relation;
    }

    private function keyConfig(string $title): KeyConfig
    {
        return $this->unsavedKeyConfig($title)->setId(self::KEY_ID);
    }

    /**
     * A key config as it is before its first save: {@see KeyConfig::getId()} answers null for it.
     */
    private function unsavedKeyConfig(string $title): KeyConfig
    {
        $keyConfig = new KeyConfig();
        $keyConfig->setType('input');
        $keyConfig->setDefinition(json_encode(['name' => 'csKey', 'title' => $title]));

        return $keyConfig;
    }

    private function classificationStoreField(string $name, ?int $storeId = null): ClassificationstoreField
    {
        $fieldDefinition = new ClassificationstoreField();
        $fieldDefinition->setName($name);

        if ($storeId !== null) {
            $fieldDefinition->setStoreId($storeId);
        }

        return $fieldDefinition;
    }

    /**
     * Definition::getByKey() answers from the runtime cache before it looks for a definition file,
     * so the brick can be built in memory.
     */
    private function registerBrick(ClassificationstoreField $fieldDefinition, string $key = self::BRICK_KEY): void
    {
        $brick = new Definition();
        $brick->setKey($key);
        $brick->setFieldDefinitions([$fieldDefinition->getName() => $fieldDefinition]);

        RuntimeCache::set('objectbrick_' . $key, $brick);
    }

    /**
     * Fieldcollection\Definition::getByKey() answers from the runtime cache the same way
     * {@see registerBrick()} relies on, under its own cache key prefix.
     */
    private function registerFieldCollection(
        ClassificationstoreField $fieldDefinition,
        string $key = self::SHARED_KEY
    ): void {
        $fieldCollection = new FieldcollectionDefinition();
        $fieldCollection->setKey($key);
        $fieldCollection->setFieldDefinitions([$fieldDefinition->getName() => $fieldDefinition]);

        RuntimeCache::set('fieldcollection_' . $key, $fieldCollection);
    }
    /**
     * A stored key config names its type as a string, so the type is only reachable from one if the
     * implementation loader maps it - and its values only round-trip through the EAV value columns
     * if a classification store marshaller is registered for it.
     */
    public function testTheMultiValueTypeAndItsMarshallerAreRegistered(): void
    {
        $loader = Pimcore::getContainer()->get('pimcore.implementation_loader.object.data');

        $this->assertTrue($loader->supports('multiValue'));
        $this->assertInstanceOf(MultiValue::class, $loader->build('multiValue'));

        $marshallerService = Pimcore::getContainer()->get(MarshallerService::class);

        $this->assertTrue($marshallerService->supportsFielddefinition('classificationstore', 'multiValue'));
        $this->assertInstanceOf(
            MultiValueMarshaller::class,
            $marshallerService->buildFieldefinitionMarshaller('classificationstore', 'multiValue')
        );
    }

    /**
     * Whether a value is packed into the two scalar value columns is asked of the whole delegate
     * chain, because a `multiValue` is registered for the format and packs nothing with that
     * registration: it hands the value to the marshaller of its inner type, and returns the value
     * unchanged in the `value` member when that type has none.
     *
     * The INVERSE WRAPPER ORDER is what the registration alone got wrong. For
     * `encryptedField<multiValue<geopoint>>` both callers read "the delegate has a marshaller, it
     * packs" and let the coordinates reach
     * {@see \Pimcore\DataObject\ClassificationstoreDataMarshaller\EncryptedField::encrypt()} as an
     * array, which casts them to the string `'Array'`: the document engine wrote no envelope and the
     * EAV engine refused nothing, and the value was lost with no error. The columns the EAV engine
     * inspects after marshalling cannot catch it either - `'Array'` is a scalar by then.
     *
     * @dataProvider packingProvider
     */
    public function testPackingIntoTheValueColumnsIsAnsweredThroughTheWholeDelegateChain(
        ?callable $build,
        bool $packs,
        string $message
    ): void {
        $marshallerService = Pimcore::getContainer()->get(MarshallerService::class);

        $this->assertSame(
            $packs,
            Service::marshallerPacksIntoValueColumns($build === null ? null : $build(), $marshallerService),
            $message
        );
    }

    /**
     * @return array<string, array{0: ?callable, 1: bool, 2: string}>
     */
    public static function packingProvider(): array
    {
        $wrap = static function (?Data $inner): MultiValue {
            $wrapper = new MultiValue();
            $wrapper->setMultiValue(true);

            if ($inner !== null) {
                $wrapper->setDelegateDatatype($inner->getFieldType());
                $wrapper->setDelegate($inner);
            }

            return $wrapper;
        };

        return [
            'no delegate at all' => [
                null,
                false,
                'a delegate that was never configured packs nothing',
            ],
            'a type with a marshaller of its own' => [
                static fn (): Data => new QuantityValue(),
                true,
                'a quantityValue is packed into the two columns by its own marshaller',
            ],
            'a type with no marshaller' => [
                static fn (): Data => new Geopoint(),
                false,
                'a geopoint has no classification store marshaller, so nothing packs its coordinates',
            ],
            'a wrapper around a type with a marshaller' => [
                static fn (): Data => $wrap(new QuantityValue()),
                true,
                'the wrapper hands the value to the quantityValue marshaller, which packs it',
            ],
            'a wrapper around a type with no marshaller' => [
                static fn (): Data => $wrap(new Geopoint()),
                false,
                'the wrapper returns the coordinates in the `value` member, which is not a column',
            ],
            'a wrapper naming no inner type' => [
                static fn (): Data => $wrap(null),
                false,
                'a wrapper with no inner type returns the value it was handed unchanged',
            ],
            'consecutive wrappers around a type with no marshaller' => [
                static fn (): Data => $wrap($wrap(new Geopoint())),
                false,
                'a chain of any length is answered by the type at the end of it',
            ],
            'consecutive wrappers around a type with a marshaller' => [
                static fn (): Data => $wrap($wrap(new QuantityValue())),
                true,
                'and the same chain ending in a type that packs does pack',
            ],
        ];
    }

    /**
     * The qualifier declarations of a multi-value key live in its definition blob, so they have to
     * survive the round trip the definition makes: setValues() puts them on the instance,
     * __set_state() rebuilds that instance from its own properties, and the delegate - which is a
     * raw array at that point - is rebuilt as the inner type afterwards, with the inner type's own
     * properties intact rather than replaced by the defaults of a fresh instance.
     */
    public function testAMultiValueKeyDefinitionRoundTripsItsDeclarationsAndItsDelegate(): void
    {
        $inner = new QuantityValue();
        $inner->setName('mvInner');
        $inner->setDefaultUnit('kg');
        $inner->setValidUnits(['kg', 'g']);

        $definition = new MultiValue();
        $definition->setName('mvKey');
        $definition->setVariants([['name' => 'metric'], ['name' => 'imperial', 'label' => 'Imperial']]);
        $definition->setMultiValue(3);
        $definition->setDelegateDatatype($inner->getFieldType());
        $definition->setDelegate($inner);

        $rebuilt = Service::getFieldDefinitionFromJson(
            json_decode((string) json_encode($definition), true),
            $definition->getFieldType()
        );

        $this->assertInstanceOf(MultiValue::class, $rebuilt);
        $this->assertSame('mvKey', $rebuilt->getName());
        $this->assertSame(
            [['name' => 'metric'], ['name' => 'imperial', 'label' => 'Imperial']],
            $rebuilt->getVariants()
        );
        $this->assertSame(['metric', 'imperial'], $rebuilt->getVariantNames());
        $this->assertTrue($rebuilt->isMultiValue());
        $this->assertSame(3, $rebuilt->getMaximum());

        $delegate = $rebuilt->getDelegate();
        $this->assertInstanceOf(QuantityValue::class, $delegate);
        $this->assertSame('kg', $delegate->getDefaultUnit());
        $this->assertSame(['kg', 'g'], $delegate->getValidUnits());
    }

    /**
     * A key declaring no qualifier at all is the ordinary case, and its declarations have to come
     * back empty rather than uninitialised - the read path tests them on every call.
     */
    public function testAMultiValueKeyWithoutDeclarationsRoundTripsAsAKeyWithoutThem(): void
    {
        $inner = new Input();
        $inner->setName('mvInner');

        $definition = new MultiValue();
        $definition->setName('mvPlainKey');
        $definition->setDelegateDatatype($inner->getFieldType());
        $definition->setDelegate($inner);

        $rebuilt = Service::getFieldDefinitionFromJson(
            json_decode((string) json_encode($definition), true),
            $definition->getFieldType()
        );

        $this->assertInstanceOf(MultiValue::class, $rebuilt);
        $this->assertSame([], $rebuilt->getVariants());
        $this->assertFalse($rebuilt->isMultiValue());
        $this->assertNull($rebuilt->getMaximum());
        $this->assertInstanceOf(Input::class, $rebuilt->getDelegate());
    }

    /**
     * A definition blob that names no inner type at all rebuilds as a delegate-less wrapper. The
     * rebuild reads the inner type off every instance it is handed, and there is no editor for this
     * type yet, so the blob arrives from a class or store import or from a hand-edited key row - an
     * unset property there would make every request that resolves the key fail with
     * "Typed property ... must not be accessed before initialization" instead.
     */
    public function testAMultiValueKeyDefinitionNamingNoInnerTypeRebuildsWithoutADelegate(): void
    {
        $rebuilt = Service::getFieldDefinitionFromJson(
            ['name' => 'mvNoDelegateKey', 'variants' => [['name' => 'metric']], 'multiValue' => true],
            'multiValue'
        );

        $this->assertInstanceOf(MultiValue::class, $rebuilt);
        $this->assertNull($rebuilt->getDelegateDatatype());
        $this->assertNull($rebuilt->getDelegate(), 'the raw decoded blob is not left behind as a definition');
        $this->assertSame(['metric'], $rebuilt->getVariantNames());
        $this->assertTrue($rebuilt->isMultiValue());
    }

}
