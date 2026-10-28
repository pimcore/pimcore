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
use Pimcore\Model\DataObject\Csstorelocalized;
use Pimcore\Model\Element\ValidationException;
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
        // every group holding a stored row is mapped, with the collection the row carries - none here
        $this->assertSame([$groupA->getId() => null], $result['groupCollectionMapping']);
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
     * @return string[]
     */
    private function getValidLanguages(): array
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
