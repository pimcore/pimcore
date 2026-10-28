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

use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Csstore;
use Pimcore\Model\DataObject\Csstorelocalized;

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
        $large = $this->createLocalizedPair('validity-large', 10, 1, 1);

        $smallCount = $this->measureCheckValidity($small);
        $largeCount = $this->measureCheckValidity($large);

        $this->assertGreaterThan(0, $smallCount, 'precondition: checking the validity reads the database');
        $this->assertSame(
            $smallCount,
            $largeCount,
            'validating 10 groups against every valid language must cost what one group costs'
            . ', measured ' . $smallCount . ' against ' . $largeCount . ' statements'
        );
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
        }

        return $activeGroups;
    }
}
