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

namespace Pimcore\Tests\Model\DataType\ClassificationStore;

use Pimcore\Model\DataObject\ClassDefinition\Data\OptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\DataObject\Csstorelocalized;
use Pimcore\Tests\Support\Util\OptionsProvider\CountingOptionsProvider;
use Pimcore\Tests\Support\Util\OptionsProvider\StaticListOptionsProvider;

/**
 * The key configs, field definitions and key/group relations of a classification store field are
 * resolved in batches, so none of its read paths issues a statement per group, per key or per
 * language.
 *
 * @group model.datatype.classificationstore
 */
class StatementCountTest extends AbstractBatchedRelationTest
{
    private const FIELDNAME = 'csstore';

    public function testLoadingDoesNotScaleWithGroupsKeysOrLanguages(): void
    {
        $small = $this->createObject('load-small', 1, 1, 1);
        $large = $this->createObject('load-large', 10, 10, 3);

        $smallCount = $this->measureLoad($small->getId());
        $largeCount = $this->measureLoad($large->getId());

        $this->assertGreaterThan(0, $smallCount, 'precondition: loading the store reads the database');
        $this->assertSame(
            $smallCount,
            $largeCount,
            'loading 10 groups x 10 keys x 3 languages must cost what one key costs'
            . ', measured ' . $smallCount . ' against ' . $largeCount . ' statements'
        );
    }

    /**
     * A value whose key was removed from its group is not covered by the relations of that group:
     * it is read through its key config instead. That fallback must not cost a statement per key
     * either.
     */
    public function testLoadingValuesOfDetachedKeysDoesNotScaleWithKeys(): void
    {
        $small = $this->createObject('detached-small', 1, 1, 1);
        $large = $this->createObject('detached-large', 1, 10, 1);

        $this->detachKeysFromTheirGroups($small);
        $this->detachKeysFromTheirGroups($large);

        $smallCount = $this->measureLoad($small->getId());
        $largeCount = $this->measureLoad($large->getId());

        $this->assertGreaterThan(0, $smallCount, 'precondition: loading the store reads the database');
        $this->assertSame(
            $smallCount,
            $largeCount,
            'loading 10 values whose key left its group must cost what one of them costs'
            . ', measured ' . $smallCount . ' against ' . $largeCount . ' statements'
        );
    }

    public function testLayoutEnrichmentDoesNotScaleWithGroups(): void
    {
        $small = $this->createObject('layout-small', 1, 1, 1);
        $large = $this->createObject('layout-large', 10, 10, 1);

        $smallCount = $this->measureLayoutEnrichment($small);
        $largeCount = $this->measureLayoutEnrichment($large);

        $this->assertGreaterThan(0, $smallCount, 'precondition: enriching the layout reads the database');
        $this->assertSame(
            $smallCount,
            $largeCount,
            'enriching the layout of 10 groups must cost what one group costs'
            . ', measured ' . $smallCount . ' against ' . $largeCount . ' statements'
        );
    }

    public function testEditModeDataDoesNotScaleWithGroupsOrLanguages(): void
    {
        $small = $this->createLocalizedPair('editmode-small', 1, 1, 1);
        $large = $this->createLocalizedPair('editmode-large', 10, 2, 3);

        $smallCount = $this->measureEditModeData($small);
        $largeCount = $this->measureEditModeData($large);

        $this->assertGreaterThan(0, $smallCount, 'precondition: building the editmode data reads the database');
        $this->assertSame(
            $smallCount,
            $largeCount,
            'the editmode data of 10 groups in 3 stored languages must cost what one key costs'
            . ', measured ' . $smallCount . ' against ' . $largeCount . ' statements'
        );
    }

    public function testCheckValidityDoesNotScaleWithGroups(): void
    {
        $small = $this->createLocalizedPair('validity-small', 1, 1, 1);
        $large = $this->createLocalizedPair('validity-large', 10, 1, 3);

        $smallCount = $this->measureCheckValidity($small);
        $largeCount = $this->measureCheckValidity($large);

        $this->assertGreaterThan(0, $smallCount, 'precondition: checking the validity reads the database');
        $this->assertSame(
            $smallCount,
            $largeCount,
            'validating 10 groups in 3 stored languages against every valid language must cost what one group costs'
            . ', measured ' . $smallCount . ' against ' . $largeCount . ' statements'
        );
    }

    /**
     * Removes every key of the object's active groups from its group and leaves the stored values
     * behind, which is what removing a key from a group in the editor produces.
     */
    private function detachKeysFromTheirGroups(Csstore $object): void
    {
        foreach (array_keys($object->getCsstore()->getActiveGroups()) as $groupId) {
            $relations = new Classificationstore\KeyGroupRelation\Listing();
            $relations->setCondition('`groupId` = ?', [$groupId]);

            foreach ($relations->load() as $relation) {
                $relation->delete();
            }
        }
    }

    private function measureLoad(int $objectId): int
    {
        $this->startMeasurement();

        $object = Csstore::getById($objectId, ['force' => true]);
        $object->getCsstore()->getItems();

        return $this->countReadStatements();
    }

    private function measureLayoutEnrichment(Csstore $object): int
    {
        $fieldDefinition = $object->getClass()->getFieldDefinition(self::FIELDNAME);

        $this->startMeasurement();

        $fieldDefinition->enrichLayoutDefinition($object);

        return $this->countReadStatements();
    }

    private function measureEditModeData(Csstorelocalized $object): int
    {
        $fieldDefinition = $object->getClass()->getFieldDefinition(self::FIELDNAME);

        $this->startMeasurement();

        $fieldDefinition->getDataForEditmode($object->getCsstore(), $object);

        return $this->countReadStatements();
    }

    private function measureCheckValidity(Csstorelocalized $object): int
    {
        $fieldDefinition = $object->getClass()->getFieldDefinition(self::FIELDNAME);

        $this->startMeasurement();

        $fieldDefinition->checkValidity($object->getCsstore());

        return $this->countReadStatements();
    }

    private function createObject(string $key, int $groupCount, int $keysPerGroup, int $languageCount): Csstore
    {
        $object = new Csstore();
        $object->setParentId(1);
        $object->setKey($key);
        $object->setPublished(true);

        $this->fillStore($object->getCsstore(), $groupCount, $keysPerGroup, $languageCount);
        $object->save();

        return $object;
    }

    /**
     * The localized class allows inheritance, so the child exercises the inherited-value branch
     * of doGetDataForEditMode() as well.
     */
    private function createLocalizedPair(
        string $key,
        int $groupCount,
        int $keysPerGroup,
        int $languageCount
    ): Csstorelocalized {
        $parent = new Csstorelocalized();
        $parent->setParentId(1);
        $parent->setKey($key . '-parent');
        $parent->setPublished(true);

        $groups = $this->fillStore($parent->getCsstore(), $groupCount, $keysPerGroup, $languageCount);
        $parent->save();

        $child = new Csstorelocalized();
        $child->setParentId($parent->getId());
        $child->setKey($key . '-child');
        $child->setPublished(true);
        $child->getCsstore()->setActiveGroups($groups);
        $child->save();

        return Csstorelocalized::getById($child->getId(), ['force' => true]);
    }

    /**
     * @return array<int, bool> the ids of the groups that were activated
     */
    private function fillStore(
        Classificationstore $store,
        int $groupCount,
        int $keysPerGroup,
        int $languageCount
    ): array {
        $languages = array_slice(['default', 'en', 'de'], 0, $languageCount);
        $activeGroups = [];

        for ($groupIndex = 0; $groupIndex < $groupCount; $groupIndex++) {
            $group = $this->createGroup('group' . $groupIndex);
            $activeGroups[$group->getId()] = true;

            for ($keyIndex = 0; $keyIndex < $keysPerGroup; $keyIndex++) {
                $keyConfig = $this->createKey('key' . $groupIndex . '_' . $keyIndex);
                $this->assignKey($group, $keyConfig, $keyIndex);

                foreach ($languages as $language) {
                    $store->setLocalizedKeyValue(
                        $group->getId(),
                        $keyConfig->getId(),
                        'value-' . $groupIndex . '-' . $keyIndex . '-' . $language,
                        $language
                    );
                }
            }

            // one key per group whose assignment carries an option subset over a list its options
            // provider fills: building its definition resolves that provider, which none of the
            // other shapes does, so the counts above have to cover it too
            $providerKey = $this->createKey(
                'provider' . $groupIndex,
                true,
                $this->providerBackedSelect(StaticListOptionsProvider::class)
            );
            $this->assignKey($group, $providerKey, $keysPerGroup, false, ['options' => ['a']]);

            foreach ($languages as $language) {
                $store->setLocalizedKeyValue($group->getId(), $providerKey->getId(), 'a', $language);
            }
        }

        return $activeGroups;
    }

    /**
     * Resolving the options provider of a key is what applying a per-group option subset to the list
     * the provider fills costs, and the core providers read a row for it
     * ({@see \Pimcore\Bundle\CoreBundle\OptionsProvider\SelectOptionsOptionsProvider::getOptions()}).
     * So it has to be paid once per assignment and per process, not once per definition built: the
     * load and the validity walk each build the definition of every key of every active group.
     */
    public function testTheOptionsProviderOfAnOverriddenKeyIsResolvedOncePerAssignment(): void
    {
        $group = $this->createGroup('provider-memo');
        $keyConfig = $this->createKey(
            'provider_memo',
            true,
            $this->providerBackedSelect(CountingOptionsProvider::class)
        );
        $this->assignKey($group, $keyConfig, 0, false, ['options' => ['a']]);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('provider-memo');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);
        $object->getCsstore()->setLocalizedKeyValue($group->getId(), $keyConfig->getId(), 'a', 'en');
        $object->save();

        $this->clearConfigCaches();
        CountingOptionsProvider::reset();

        $reloaded = Csstorelocalized::getById($object->getId(), ['force' => true]);
        $reloaded->getCsstore()->getItems();
        $reloaded->getClass()->getFieldDefinition(self::FIELDNAME)->checkValidity($reloaded->getCsstore());

        $this->assertSame(
            1,
            CountingOptionsProvider::$calls,
            'the provider of one assignment must be asked once however many definitions are built'
            . ', measured ' . CountingOptionsProvider::$calls . ' calls'
        );
    }

    private function providerBackedSelect(string $providerClass): Select
    {
        $select = new Select();
        $select->setOptionsProviderType(OptionsProviderInterface::TYPE_CLASS);
        $select->setOptionsProviderClass($providerClass);

        return $select;
    }
}
