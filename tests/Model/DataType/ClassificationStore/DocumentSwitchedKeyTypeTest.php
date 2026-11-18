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

use Pimcore\Model\DataObject\ClassDefinition\Data\Consent;
use Pimcore\Model\DataObject\ClassDefinition\Data\Date;
use Pimcore\Model\DataObject\ClassDefinition\Data\Geopoint;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Model\DataObject\Classificationstore;

/**
 * {@see SwitchedKeyTypeTest} against a store on the document engine, which holds the normalized value
 * itself rather than two scalar value columns - so this is the engine a stored value of a foreign
 * SHAPE can reach a key's type on, and the engine the report came from.
 *
 * The cases below are the ones only this engine can have: a key may be of a type whose value is a
 * structure, or a relation, neither of which an EAV store accepts.
 *
 * @group model.datatype.classificationstore
 */
class DocumentSwitchedKeyTypeTest extends SwitchedKeyTypeTest
{
    protected function getStoreEngine(): ?string
    {
        return 'document';
    }

    /**
     * The opposite direction of the reported one: a stored value in front of a RELATION type. Every
     * relation reads `$elementData['type']` off each entry without asking whether it is there
     * ({@see ManyToManyObjectRelation::denormalize()}), so a list of plain strings raised a
     * `TypeError` - and raised it while the object was being hydrated, so the object could not be
     * instantiated at all rather than merely read.
     *
     * This is also the path the engine does NOT decode itself: a relation-typed key keeps its stored
     * value wrapped as a {@see Classificationstore\RelationDescriptor} until the key is read, so the
     * question is put where that descriptor is resolved
     * ({@see Classificationstore\RelationResolver::resolve()}) rather than at the engine boundary.
     */
    public function testAStoredListIsNotReadByAKeyWhoseTypeIsNowARelation(): void
    {
        [$groupId, $keyId, $objectId] = $this->storeAList('relation');

        $this->switchKeyTo($keyId, new ManyToManyObjectRelation());

        $this->assertSame(
            [],
            $this->readKey($objectId, $groupId, $keyId),
            'the key holds no relation, which is what a to-many relation holding nothing reads as'
        );
    }

    /**
     * And a to-ONE relation, which reads one descriptor rather than a list of them: handed the list
     * the old type stored it read `$value['type']` off the list itself
     * ({@see ManyToOneRelation::denormalize()}).
     */
    public function testAStoredListIsNotReadByAKeyWhoseTypeIsNowAToOneRelation(): void
    {
        [$groupId, $keyId, $objectId] = $this->storeAList('toOneRelation');

        $this->switchKeyTo($keyId, new ManyToOneRelation());

        $this->assertNull($this->readKey($objectId, $groupId, $keyId));
    }

    /**
     * A type whose value is a STRUCTURE reads the members it is made of off the stored value, with an
     * `is_array()` in front of them and nothing else: `$value['consent']`
     * ({@see Consent::denormalize()}) and `$value['latitude']` ({@see Geopoint::denormalize()}). A
     * foreign array clears the `is_array()` and the undefined member raised, which in dev is an
     * `ErrorException` - the same symptom the relation list had in front of an `input`.
     *
     * @dataProvider structuredTypeProvider
     */
    public function testAStoredListIsNotReadByAKeyWhoseTypeIsNowStructured(string $suffix, callable $build): void
    {
        [$groupId, $keyId, $objectId] = $this->storeAList($suffix);

        $this->switchKeyTo($keyId, $build());

        $this->assertNull($this->readKey($objectId, $groupId, $keyId));
    }

    /**
     * @return array<string, array{0: string, 1: callable}>
     */
    public static function structuredTypeProvider(): array
    {
        return [
            'consent' => ['consent', fn () => new Consent()],
            'geopoint' => ['geopoint', fn () => new Geopoint()],
        ];
    }

    /**
     * A `date` holds a timestamp, which its value object says nothing about: it hands the stored
     * value to a helper declaring `float|int|string`
     * ({@see Date::denormalize()}), so the list the old type stored raised a `TypeError` there.
     */
    public function testAStoredListIsNotReadByAKeyWhoseTypeIsNowADate(): void
    {
        [$groupId, $keyId, $objectId] = $this->storeAList('date');

        $this->switchKeyTo($keyId, new Date());

        $this->assertNull($this->readKey($objectId, $groupId, $keyId));
    }
}
