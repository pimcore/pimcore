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

use Pimcore\Model\DataObject\ClassDefinition\Data\CalculatedValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\OptionsProviderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Csstorelocalized;
use Pimcore\Model\DataObject\Data\QuantityValue as QuantityValueData;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Tests\Support\Util\OptionsProvider\FailingOptionsProvider;
use Pimcore\Tests\Support\Util\OptionsProvider\StaticListOptionsProvider;
use Pimcore\Tool;

/**
 * What the batched key set changes for the two paths that consume it with a language dimension:
 * the editmode payload and the mandatory check. Both keep today's semantics.
 *
 * @group model.datatype.classificationstore
 */
class ValidityAndEditModeTest extends AbstractBatchedRelationTest
{
    private const FIELDNAME = 'csstore';

    private const OPTIONS = 'options';

    private const LANGUAGE = 'en';

    /**
     * The EAV engine maps every group holding a stored value, with a null collection when it has none.
     *
     * @return array<int, int|null>
     */
    protected function expectedMappingOfAGroupWithoutCollection(int $groupId): array
    {
        return [$groupId => null];
    }

    public function testEditModePayloadOfAParentChildPair(): void
    {
        $groupA = $this->createGroup('editmode_a');
        $groupB = $this->createGroup('editmode_b');

        $inputKey = $this->createKey('editmode_input');
        $calculatedKey = $this->createKey('editmode_calculated', true, $this->createCalculatedValueDefinition());
        $otherKey = $this->createKey('editmode_other');

        $this->assignKey($groupA, $inputKey, 0);
        $this->assignKey($groupA, $calculatedKey, 1);
        $this->assignKey($groupB, $otherKey, 0);

        $parent = new Csstorelocalized();
        $parent->setParentId(1);
        $parent->setKey('editmode-parent');
        $parent->setPublished(true);
        $parent->getCsstore()->setLocalizedKeyValue($groupA->getId(), $inputKey->getId(), 'parent input', 'en');
        $parent->getCsstore()->setLocalizedKeyValue($groupB->getId(), $otherKey->getId(), 'parent other', 'de');
        $parent->save();

        $child = new Csstorelocalized();
        $child->setParentId($parent->getId());
        $child->setKey('editmode-child');
        $child->setPublished(true);
        $child->getCsstore()->setActiveGroups([$groupA->getId() => true, $groupB->getId() => true]);
        $child->getCsstore()->setLocalizedKeyValue($groupA->getId(), $inputKey->getId(), 'child input', 'en');
        $child->save();

        $child = Csstorelocalized::getById($child->getId(), ['force' => true]);
        $fieldDefinition = $child->getClass()->getFieldDefinition(self::FIELDNAME);

        $result = $fieldDefinition->getDataForEditmode($child->getCsstore(), $child);

        $this->assertEquals(
            [$groupA->getId() => true, $groupB->getId() => true],
            $result['activeGroups']
        );
        $this->assertSame($this->expectedMappingOfAGroupWithoutCollection($groupA->getId()), $result['groupCollectionMapping']);
        $this->assertTrue($result['inherited']);

        $this->assertSame('child input', $result['data']['en'][$groupA->getId()][$inputKey->getId()]);
        $this->assertSame('parent other', $result['data']['de'][$groupB->getId()][$otherKey->getId()]);

        $this->assertSame(
            ['inherited' => false, 'objectid' => $child->getId()],
            $result['metaData']['en'][$groupA->getId()][$inputKey->getId()]
        );
        // a value the parent carries is merged into the child's items before the flags are built,
        // so it is reported against the child - as it is today
        $this->assertSame(
            ['inherited' => false, 'objectid' => $child->getId()],
            $result['metaData']['de'][$groupB->getId()][$otherKey->getId()]
        );
        // a key empty on both sides is the one that reports the parent
        $this->assertSame(
            ['inherited' => true, 'objectid' => $parent->getId()],
            $result['metaData']['en'][$groupB->getId()][$otherKey->getId()]
        );

        // the calculated key is answered for every valid language, in its own group only
        foreach ($this->getValidLanguages() as $language) {
            $this->assertArrayHasKey(
                $calculatedKey->getId(),
                $result['data'][$language][$groupA->getId()],
                'the calculated value is part of the payload of language ' . $language
            );
            $this->assertArrayNotHasKey(
                $calculatedKey->getId(),
                $result['data'][$language][$groupB->getId()] ?? [],
                'and only of the group it is assigned to'
            );
        }
    }

    public function testAValueIsValidatedAgainstTheOptionSubsetOfItsOwnGroup(): void
    {
        $groupA = $this->createGroup('subset_a');
        $groupB = $this->createGroup('subset_b');

        $select = new Select();
        $select->setOptions([['key' => 'Option A', 'value' => 'a'], ['key' => 'Option B', 'value' => 'b']]);
        $select->setEnforceValidation(true);
        $keyConfig = $this->createKey('subset', true, $select);

        $this->assignKey($groupA, $keyConfig, 0, false, [self::OPTIONS => ['a']]);
        $this->assignKey($groupB, $keyConfig, 0, false, [self::OPTIONS => ['b']]);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('subset');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$groupA->getId() => true, $groupB->getId() => true]);
        $object->getCsstore()->setLocalizedKeyValue($groupA->getId(), $keyConfig->getId(), 'a', 'en');
        $object->getCsstore()->setLocalizedKeyValue($groupB->getId(), $keyConfig->getId(), 'b', 'en');
        $object->save();

        // 'b' is a valid option of the key, but not of the group A assigns it to
        $object->getCsstore()->setLocalizedKeyValue($groupA->getId(), $keyConfig->getId(), 'b', 'en');

        $this->expectException(ValidationException::class);

        $object->save();
    }

    public function testAValueIsValidatedAgainstTheSubsetOfTheListAProviderFills(): void
    {
        $group = $this->createGroup('provider_subset');

        $select = new Select();
        $select->setOptionsProviderType(OptionsProviderInterface::TYPE_CLASS);
        $select->setOptionsProviderClass(StaticListOptionsProvider::class);
        $select->setEnforceValidation(true);
        $keyConfig = $this->createKey('provider_subset', true, $select);

        $this->assignKey($group, $keyConfig, 0, false, [self::OPTIONS => ['a']]);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('provider-subset');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);
        $object->getCsstore()->setLocalizedKeyValue($group->getId(), $keyConfig->getId(), 'a', 'en');
        $object->save();

        // 'b' is an option of the list the provider fills, but not of the subset this group narrows it to
        $object->getCsstore()->setLocalizedKeyValue($group->getId(), $keyConfig->getId(), 'b', 'en');

        $this->expectException(ValidationException::class);

        $object->save();
    }

    /**
     * A subset naming no option the key still offers - what editing the key out from under an
     * assignment leaves behind - refuses every value rather than switching the option check off.
     */
    public function testASubsetThatLeavesNoOptionOfTheKeyRefusesEveryValue(): void
    {
        $group = $this->createGroup('empty_subset');

        $select = new Select();
        $select->setOptions([['key' => 'Option A', 'value' => 'a'], ['key' => 'Option B', 'value' => 'b']]);
        $select->setEnforceValidation(true);
        $keyConfig = $this->createKey('empty_subset', true, $select);

        $this->assignKey($group, $keyConfig, 0, false, [self::OPTIONS => ['gone']]);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('empty-subset');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);
        $object->save();

        $this->assertNotNull(
            Csstorelocalized::getById($object->getId(), ['force' => true]),
            'a key holding no value is not refused'
        );

        // an option of the key, and of no group's subset
        $object->getCsstore()->setLocalizedKeyValue($group->getId(), $keyConfig->getId(), 'a', 'en');

        $this->expectException(ValidationException::class);

        $object->save();
    }

    /**
     * The same for the list a provider fills: the group's exclusions have to be enforced against it,
     * where an unset list would have the provider's whole list validated against instead.
     */
    public function testASubsetThatMatchesNoProvidedOptionRefusesEveryValue(): void
    {
        $group = $this->createGroup('empty_provider_subset');

        $select = new Select();
        $select->setOptionsProviderType(OptionsProviderInterface::TYPE_CLASS);
        $select->setOptionsProviderClass(StaticListOptionsProvider::class);
        $select->setEnforceValidation(true);
        $keyConfig = $this->createKey('empty_provider_subset', true, $select);

        $this->assignKey($group, $keyConfig, 0, false, [self::OPTIONS => ['gone']]);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('empty-provider-subset');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);
        // an option the provider offers, and one the group excludes
        $object->getCsstore()->setLocalizedKeyValue($group->getId(), $keyConfig->getId(), 'b', 'en');

        $this->expectException(ValidationException::class);

        $object->save();
    }

    /**
     * Applying a subset means asking the key's options provider, which the load and the save of an
     * object did not do before. A provider that cannot answer - its configuration deleted - narrows
     * nothing, and must not make reading or writing the store fail.
     */
    public function testAnOptionsProviderThatCannotAnswerLeavesTheSaveAndTheLoadAlone(): void
    {
        $group = $this->createGroup('failing_provider');

        $select = new Select();
        $select->setOptionsProviderType(OptionsProviderInterface::TYPE_CLASS);
        $select->setOptionsProviderClass(FailingOptionsProvider::class);
        $keyConfig = $this->createKey('failing_provider', true, $select);

        $this->assignKey($group, $keyConfig, 0, false, [self::OPTIONS => ['a']]);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('failing-provider');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);
        $object->getCsstore()->setLocalizedKeyValue($group->getId(), $keyConfig->getId(), 'a', 'en');
        $object->save();

        $this->getRelationCache()->reset();

        $reloaded = Csstorelocalized::getById($object->getId(), ['force' => true]);

        $this->assertSame(
            'a',
            $reloaded->getCsstore()->getLocalizedKeyValue($group->getId(), $keyConfig->getId(), 'en')
        );
    }

    /**
     * With enforceValidation on, Select::checkValidity() resolves the provider itself for options left
     * unset, so the save has to be tolerant of the provider as well, not only the definition build.
     */
    public function testAnOptionsProviderThatCannotAnswerDoesNotFailAnEnforcingSave(): void
    {
        $group = $this->createGroup('failing_provider_enforced');

        $select = new Select();
        $select->setOptionsProviderType(OptionsProviderInterface::TYPE_CLASS);
        $select->setOptionsProviderClass(FailingOptionsProvider::class);
        $select->setEnforceValidation(true);
        $keyConfig = $this->createKey('failing_provider_enforced', true, $select);

        $this->assignKey($group, $keyConfig, 0, false, [self::OPTIONS => ['a']]);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('failing-provider-enforced');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);
        $object->getCsstore()->setLocalizedKeyValue($group->getId(), $keyConfig->getId(), 'a', 'en');
        $object->save();

        $this->assertSame(
            'a',
            Csstorelocalized::getById($object->getId(), ['force' => true])
                ->getCsstore()->getLocalizedKeyValue($group->getId(), $keyConfig->getId(), 'en')
        );
    }

    public function testValuesOfAKeyInTwoGroupsRoundTripThroughEditModeAndSave(): void
    {
        $groupA = $this->createGroup('units_a');
        $groupB = $this->createGroup('units_b');

        $millimetre = $this->getUnit('mm')->getId();
        $centimetre = $this->getUnit('cm')->getId();

        $quantity = new QuantityValue();
        $quantity->setDefaultUnit($centimetre);
        $quantity->setValidUnits([$centimetre, $millimetre]);
        $keyConfig = $this->createKey('units', true, $quantity);

        $this->assignKey($groupA, $keyConfig, 0, false, ['defaultUnit' => $millimetre, 'validUnits' => [$millimetre]]);
        $this->assignKey($groupB, $keyConfig, 0, false, ['defaultUnit' => $centimetre]);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('units');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$groupA->getId() => true, $groupB->getId() => true]);
        $object->getCsstore()->setLocalizedKeyValue($groupA->getId(), $keyConfig->getId(), new QuantityValueData(5, $millimetre), 'en');
        $object->getCsstore()->setLocalizedKeyValue($groupB->getId(), $keyConfig->getId(), new QuantityValueData(7, $centimetre), 'en');
        $object->save();

        $object = Csstorelocalized::getById($object->getId(), ['force' => true]);
        $fieldDefinition = $object->getClass()->getFieldDefinition(self::FIELDNAME);

        $payload = $fieldDefinition->getDataForEditmode($object->getCsstore(), $object);

        $this->assertEquals(5, $payload['data']['en'][$groupA->getId()][$keyConfig->getId()]['value']);
        $this->assertEquals(7, $payload['data']['en'][$groupB->getId()][$keyConfig->getId()]['value']);

        $restored = $fieldDefinition->getDataFromEditmode($payload, $object);

        $this->assertEquals(5, $restored->getLocalizedKeyValue($groupA->getId(), $keyConfig->getId(), 'en')->getValue());
        $this->assertSame($centimetre, $restored->getLocalizedKeyValue($groupB->getId(), $keyConfig->getId(), 'en')->getUnitId());
    }

    private function getUnit(string $abbreviation): Unit
    {
        $unit = Unit::getByAbbreviation($abbreviation);
        if (!$unit) {
            $unit = new Unit();
            $unit->setAbbreviation($abbreviation);
            $unit->save();
        }

        return $unit;
    }

    public function testADisabledKeyWithAMandatoryRelationIsStillValidated(): void
    {
        $group = $this->createGroup('validity_disabled');
        $keyConfig = $this->createKey('validity_disabled', false);
        $this->assignKey($group, $keyConfig, 0, true);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('validity-disabled');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);

        $this->expectException(ValidationException::class);

        $object->save();
    }

    public function testEveryValidLanguageIsChecked(): void
    {
        $group = $this->createGroup('validity_languages');
        $keyConfig = $this->createKey('validity_languages');
        $this->assignKey($group, $keyConfig, 0, true);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('validity-languages');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);

        $fieldDefinition = $object->getClass()->getFieldDefinition(self::FIELDNAME);

        try {
            $fieldDefinition->checkValidity($object->getCsstore());
            $this->fail('an empty mandatory key must not validate');
        } catch (ValidationException $validationException) {
            $messages = array_map(
                static fn (ValidationException $subItem) => $subItem->getMessage(),
                $validationException->getSubItems()
            );

            $this->assertCount(count($this->getValidLanguages()), $messages);

            foreach ($this->getValidLanguages() as $language) {
                $this->assertStringContainsString('(' . $language . ')', implode(' | ', $messages));
            }
        }
    }

    public function testAnActiveGroupWithoutAConfigContributesNothing(): void
    {
        $group = $this->createGroup('validity_vanished');
        $keyConfig = $this->createKey('validity_vanished');
        $this->assignKey($group, $keyConfig, 0, true);

        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey('validity-vanished');
        $object->setPublished(true);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);

        $fieldDefinition = $object->getClass()->getFieldDefinition(self::FIELDNAME);

        $violated = false;

        try {
            $fieldDefinition->checkValidity($object->getCsstore());
        } catch (ValidationException) {
            $violated = true;
        }

        $this->assertTrue($violated, 'precondition: the empty mandatory key of the group is a violation');

        $group->delete();
        $this->getRelationCache()->reset();

        $fieldDefinition->checkValidity($object->getCsstore());

        $this->addToAssertionCount(1);
    }

    /**
     * A draft save omits the mandatory check and nothing else on the store's own account: a value
     * outside the key's options is refused as on a published save, one inside them is saved.
     */
    public function testADraftSaveRefusesAValueOutsideTheKeysOptions(): void
    {
        $group = $this->createGroup('draft_options');
        $keyConfig = $this->createKey('draft_options', true, $this->createEnforcingSelect());
        $this->assignKey($group, $keyConfig);

        foreach ([false, true] as $published) {
            $object = $this->createStoreObject('draft-options-' . (int) $published, $published, $group);
            $object->getCsstore()->setLocalizedKeyValue($group->getId(), $keyConfig->getId(), 'gamma', self::LANGUAGE);

            $this->assertSaveRefusedWith($object, "Invalid option 'gamma' for field [");
        }
    }

    public function testADraftSaveOfAValueInsideTheKeysOptionsIsSaved(): void
    {
        $group = $this->createGroup('draft_options_valid');
        $keyConfig = $this->createKey('draft_options_valid', true, $this->createEnforcingSelect());
        $this->assignKey($group, $keyConfig);

        foreach ([false, true] as $published) {
            $object = $this->createStoreObject('draft-valid-' . (int) $published, $published, $group);
            $object->getCsstore()->setLocalizedKeyValue($group->getId(), $keyConfig->getId(), 'alpha', self::LANGUAGE);
            $object->save();

            $reloaded = Csstorelocalized::getById($object->getId(), ['force' => true]);
            $this->assertSame(
                'alpha',
                $reloaded->getCsstore()->getLocalizedKeyValue($group->getId(), $keyConfig->getId(), self::LANGUAGE)
            );
        }
    }

    public function testADraftSaveOfAnEmptyMandatoryKeyIsSavedAndAPublishedOneIsRefused(): void
    {
        $group = $this->createGroup('draft_mandatory');
        $keyConfig = $this->createKey('draft_mandatory');
        $this->assignKey($group, $keyConfig, 0, true);

        $draft = $this->createStoreObject('draft-mandatory-draft', false, $group);
        $draft->save();
        $this->assertNotNull(Csstorelocalized::getById($draft->getId(), ['force' => true]));

        $this->assertSaveRefusedWith(
            $this->createStoreObject('draft-mandatory-published', true, $group),
            'Empty mandatory field [ '
        );
    }

    public function testADraftSaveIsRefusedForAValueTheGroupsOptionSubsetExcludes(): void
    {
        $group = $this->createGroup('draft_subset');
        // 'gamma' is an option of the key itself, so only the group's subset can exclude it
        $keyConfig = $this->createKey('draft_subset', true, $this->createEnforcingSelect(true));
        $this->assignKey($group, $keyConfig, 0, false, [self::OPTIONS => ['alpha', 'beta']]);

        foreach ([false, true] as $published) {
            $excluded = $this->createStoreObject('draft-subset-excluded-' . (int) $published, $published, $group);
            $excluded->getCsstore()->setLocalizedKeyValue(
                $group->getId(),
                $keyConfig->getId(),
                'gamma',
                self::LANGUAGE
            );
            $this->assertSaveRefusedWith($excluded, "Invalid option 'gamma'");
        }

        $allowed = $this->createStoreObject('draft-subset-allowed', false, $group);
        $allowed->getCsstore()->setLocalizedKeyValue($group->getId(), $keyConfig->getId(), 'beta', self::LANGUAGE);
        $allowed->save();
        $this->assertNotNull(Csstorelocalized::getById($allowed->getId(), ['force' => true]));
    }

    public function testADraftSaveIsRefusedWhenTheSubsetFiltersEveryOptionAway(): void
    {
        $group = $this->createGroup('draft_empty_subset');
        $keyConfig = $this->createKey('draft_empty_subset', true, $this->createEnforcingSelect());
        $this->assignKey($group, $keyConfig, 0, false, [self::OPTIONS => ['gone']]);

        foreach ([false, true] as $published) {
            $object = $this->createStoreObject('draft-empty-subset-' . (int) $published, $published, $group);
            $object->getCsstore()->setLocalizedKeyValue($group->getId(), $keyConfig->getId(), 'alpha', self::LANGUAGE);

            $this->assertSaveRefusedWith($object, "Invalid option 'alpha'");
        }
    }

    /**
     * A regex is a constraint core gates on the mandatory flag, so a draft keeps it off exactly as for
     * the same type on the object; the column length is not gated and refuses on both.
     */
    public function testADraftSaveSkipsTheRegexOfAKeyButNotItsColumnLength(): void
    {
        $input = new Input();
        $input->setRegex('^[A-Z]{2}$');
        $input->setColumnLength(5);

        $group = $this->createGroup('draft_input');
        $keyConfig = $this->createKey('draft_input', true, $input);
        $this->assignKey($group, $keyConfig);

        $draft = $this->createStoreObject('draft-input-regex-draft', false, $group);
        $draft->getCsstore()->setLocalizedKeyValue($group->getId(), $keyConfig->getId(), 'abc', self::LANGUAGE);
        $draft->save();
        $this->assertNotNull(Csstorelocalized::getById($draft->getId(), ['force' => true]));

        $published = $this->createStoreObject('draft-input-regex-published', true, $group);
        $published->getCsstore()->setLocalizedKeyValue($group->getId(), $keyConfig->getId(), 'abc', self::LANGUAGE);
        $this->assertSaveRefusedWith($published, "doesn't match input validation");

        // a value the regex accepts, so only the length can refuse it
        $lengthOnly = new Input();
        $lengthOnly->setRegex('^[A-Z]+$');
        $lengthOnly->setColumnLength(5);
        $lengthKey = $this->createKey('draft_input_length', true, $lengthOnly);
        $this->assignKey($group, $lengthKey);

        foreach ([false, true] as $isPublished) {
            $object = $this->createStoreObject('draft-input-length-' . (int) $isPublished, $isPublished, $group);
            $object->getCsstore()->setLocalizedKeyValue(
                $group->getId(),
                $lengthKey->getId(),
                'ABCDEFGH',
                self::LANGUAGE
            );

            $this->assertSaveRefusedWith($object, 'is longer than 5 characters');
        }
    }

    public function testTheGroupLimitOfTheFieldIsEnforcedOnADraftSave(): void
    {
        $groupA = $this->createGroup('draft_max_items_a');
        $groupB = $this->createGroup('draft_max_items_b');
        $fieldDefinition = (new Csstorelocalized())->getClass()->getFieldDefinition(self::FIELDNAME);
        $original = $fieldDefinition->getMaxItems();
        $fieldDefinition->setMaxItems(1);

        try {
            foreach ([false, true] as $published) {
                $object = $this->createStoreObject('draft-max-items-' . (int) $published, $published, $groupA);
                $object->getCsstore()->setActiveGroups([$groupA->getId() => true, $groupB->getId() => true]);

                $this->assertSaveRefusedWith($object, 'is bigger than 1');
            }
        } finally {
            $fieldDefinition->setMaxItems($original);
        }
    }

    private function createEnforcingSelect(bool $withGamma = false): Select
    {
        $options = [['key' => 'Alpha', 'value' => 'alpha'], ['key' => 'Beta', 'value' => 'beta']];
        if ($withGamma) {
            $options[] = ['key' => 'Gamma', 'value' => 'gamma'];
        }
        $select = new Select();
        $select->setOptions($options);
        $select->setEnforceValidation(true);

        return $select;
    }

    private function createStoreObject(
        string $key,
        bool $published,
        Classificationstore\GroupConfig $group
    ): Csstorelocalized {
        $object = new Csstorelocalized();
        $object->setParentId(1);
        $object->setKey($key);
        $object->setPublished($published);
        $object->getCsstore()->setActiveGroups([$group->getId() => true]);

        return $object;
    }

    private function assertSaveRefusedWith(Csstorelocalized $object, string $message): void
    {
        try {
            $object->save();
            $this->fail('the save must be refused');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());
        }
    }

    /**
     * @return string[]
     */
    protected function getValidLanguages(): array
    {
        $validLanguages = Tool::getValidLanguages();
        array_unshift($validLanguages, 'default');

        return $validLanguages;
    }

    private function createCalculatedValueDefinition(): CalculatedValue
    {
        $definition = new CalculatedValue();
        $definition->setCalculatorClass('@test.calculatorservice');

        return $definition;
    }
}
