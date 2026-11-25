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

use Doctrine\DBAL\Connection;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation;
use Pimcore\Model\DataObject\Classificationstore\RelationResolver;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Validating the stored descriptors of a relation-typed key without instantiating a target.
 *
 * Two properties are asserted here, and both are about validation not depending on whether the key
 * had been read before the save.
 *
 * The constraints are measured over the descriptors whose target still exists, which is what the
 * key's own `checkValidity()` is measured over: it is handed the denormalized value, and
 * `denormalize()` has already dropped the dead ones. A dead descriptor counted towards the item
 * limit would refuse a save that passes as soon as the key is read once.
 *
 * And the four constraints are claimed for the five relation types core ships alone. A type a
 * project adds may refuse what those four allow from an overridden `checkValidity()`, so it keeps
 * its own validator - the same restriction, for the same reason, that
 * {@see \Pimcore\Model\DataObject\Classificationstore\RelationEditModeResolver::supports()} applies
 * to the rendering.
 */
class RelationStoredValidityTest extends TestCase
{
    private const TARGET_ALIVE = 11;

    private const TARGET_ALSO_ALIVE = 12;

    private const TARGET_DELETED = 13;

    /**
     * @return array<string, array{0: string}>
     */
    public static function coreRelationTypeProvider(): array
    {
        return [
            'manyToOneRelation' => [ManyToOneRelation::class],
            'manyToManyRelation' => [ManyToManyRelation::class],
            'manyToManyObjectRelation' => [ManyToManyObjectRelation::class],
            'advancedManyToManyRelation' => [AdvancedManyToManyRelation::class],
            'advancedManyToManyObjectRelation' => [AdvancedManyToManyObjectRelation::class],
        ];
    }

    /**
     * @dataProvider coreRelationTypeProvider
     */
    public function testEveryRelationTypeCoreShipsIsValidatedFromItsDescriptors(string $className): void
    {
        $this->assertTrue($this->resolver()->isCoreRelation(new $className()));
    }

    public function testAProjectTypeExtendingACoreOneKeepsItsOwnValidator(): void
    {
        $this->assertFalse(
            $this->resolver()->isCoreRelation(new RelationStoredValidityTestProjectRelation()),
            'a subclass may refuse what the four core constraints allow, so it has to be asked itself'
        );
    }

    /**
     * A relation type core ships that is not one of the five: its value is computed rather than
     * stored, and nothing about it is answered from descriptors either.
     */
    public function testARelationTypeOutsideTheFiveIsNotValidatedFromItsDescriptors(): void
    {
        $this->assertFalse($this->resolver()->isCoreRelation(new ReverseObjectRelation()));
    }

    public function testAKeyThatIsNoRelationIsNotValidatedFromItsDescriptors(): void
    {
        $this->assertFalse($this->resolver()->isCoreRelation(new Input()));
    }

    public function testNoDefinitionIsNotValidatedFromItsDescriptors(): void
    {
        $this->assertFalse($this->resolver()->isCoreRelation(null));
    }

    /**
     * The case a read would have fixed: two stored targets, one of them deleted, and a limit of
     * one. Reading the key drops the dead descriptor and the same save passes, so counting it here
     * would make the save's outcome depend on the read.
     */
    public function testTheItemLimitCountsOnlyTheDescriptorsWhoseTargetStillExists(): void
    {
        $this->expectNotToPerformAssertions();

        $this->resolver()->checkStoredValidity(
            $this->objectRelation(1),
            $this->descriptorsFor(self::TARGET_ALIVE, self::TARGET_DELETED),
            $this->summariesFor(self::TARGET_ALIVE)
        );
    }

    public function testTheItemLimitIsStillExceededByTheSurvivingDescriptors(): void
    {
        $fieldDefinition = $this->objectRelation(1);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Number of allowed relations in field `csrelation` exceeded (max. 1)');

        $this->resolver()->checkStoredValidity(
            $fieldDefinition,
            $this->descriptorsFor(self::TARGET_ALIVE, self::TARGET_ALSO_ALIVE, self::TARGET_DELETED),
            $this->summariesFor(self::TARGET_ALIVE, self::TARGET_ALSO_ALIVE)
        );
    }

    /**
     * Every relation type counts the entries of its value rather than the distinct targets among
     * them, {@see ManyToManyRelation::checkValidity()}, so a key allowing multiple assignments
     * reaches its limit on repeats of one target.
     */
    public function testTheItemLimitCountsRepeatsOfOneTargetWhenMultipleAssignmentsAreAllowed(): void
    {
        $fieldDefinition = new AdvancedManyToManyRelation();
        $fieldDefinition->setName('csrelation');
        $fieldDefinition->setObjectsAllowed(true);
        $fieldDefinition->setMaxItems(2);
        $fieldDefinition->setAllowMultipleAssignments(true);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Number of allowed relations in field `csrelation` exceeded (max. 2)');

        $this->resolver()->checkStoredValidity(
            $fieldDefinition,
            $this->advancedDescriptorsFor(self::TARGET_ALIVE, self::TARGET_ALIVE, self::TARGET_ALIVE),
            $this->summariesFor(self::TARGET_ALIVE)
        );
    }

    /**
     * The same rule on the other constraint: with every target deleted the value a read answers
     * with is the empty one, and the key's own check refuses it as the mandatory key it is.
     */
    public function testAMandatoryKeyWhoseTargetsHaveAllBeenDeletedIsEmpty(): void
    {
        $fieldDefinition = $this->objectRelation(null);
        $fieldDefinition->setMandatory(true);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Empty mandatory field [ csrelation ]');

        $this->resolver()->checkStoredValidity(
            $fieldDefinition,
            $this->descriptorsFor(self::TARGET_DELETED),
            $this->summariesFor(self::TARGET_ALIVE)
        );
    }

    public function testAMandatoryKeyWithOneSurvivingTargetIsSatisfied(): void
    {
        $this->expectNotToPerformAssertions();

        $fieldDefinition = $this->objectRelation(null);
        $fieldDefinition->setMandatory(true);

        $this->resolver()->checkStoredValidity(
            $fieldDefinition,
            $this->descriptorsFor(self::TARGET_DELETED, self::TARGET_ALIVE),
            $this->summariesFor(self::TARGET_ALIVE)
        );
    }

    private function objectRelation(?int $maxItems): ManyToManyRelation
    {
        $fieldDefinition = new ManyToManyRelation();
        $fieldDefinition->setName('csrelation');
        $fieldDefinition->setObjectsAllowed(true);
        $fieldDefinition->setMaxItems($maxItems);

        return $fieldDefinition;
    }

    /**
     * What {@see ManyToManyRelation::normalize()} writes into the document for those targets.
     *
     * @return list<array{type: string, id: int}>
     */
    private function descriptorsFor(int ...$ids): array
    {
        return array_map(
            static fn (int $id): array => ['type' => 'object', 'id' => $id],
            $ids
        );
    }

    /**
     * What {@see AdvancedManyToManyRelation::normalize()} writes: the descriptor under `element`,
     * next to the per-relation column values of the entry.
     *
     * @return list<array<string, mixed>>
     */
    private function advancedDescriptorsFor(int ...$ids): array
    {
        return array_map(
            static fn (int $id): array => [
                'element' => ['type' => 'object', 'id' => $id],
                'fieldname' => 'csrelation',
                'columns' => [],
                'data' => [],
            ],
            $ids
        );
    }

    /**
     * {@see RelationResolver::summarize()} over the targets that still exist, carrying the columns
     * a target is described by. A target that is gone is simply absent, which is how the check
     * tells a dead descriptor from a live one - no lookup is made here, so no connection is used
     * either.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function summariesFor(int ...$ids): array
    {
        $summaries = [];

        foreach ($ids as $id) {
            $summaries['object'][$id] = [
                'id' => $id,
                'type' => 'object',
                'subtype' => 'object',
                'className' => 'unittestclass',
            ];
        }

        return $summaries;
    }

    private function resolver(): RelationResolver
    {
        return new RelationResolver($this->createMock(Connection::class));
    }
}

/**
 * A project relation type, as a bundle that extends a core one would declare it.
 */
class RelationStoredValidityTestProjectRelation extends ManyToManyObjectRelation
{
}
