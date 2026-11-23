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

use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
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

    /**
     * The REVERSE of the switch above: an advanced relation key changed to a plain one. The two
     * write a different entry - an advanced one nests its target under `element` and carries the
     * per-relation members beside it, a plain one names the target in the entry itself - and each
     * reads only its own. Handed an advanced entry, a plain relation reads `$elementData['type']`
     * where there is none ({@see ManyToManyObjectRelation::denormalize()}).
     *
     * The target of such an entry is still found by the one place that knows the shape
     * ({@see Classificationstore\RelationDescriptor::describeOne()}), because the dependency walks
     * and the edit mode rendering run over the stored values of plain and advanced keys alike - so
     * the plain/advanced distinction is taken where the READABILITY question is asked instead.
     */
    public function testTheEntriesOfAnAdvancedRelationAreNotReadByAPlainOne(): void
    {
        [$groupId, $keyId, $objectId] = $this->storeAList('advancedToPlain');

        // what an advancedManyToManyObjectRelation key stored for the key, naming a target that
        // exists so that nothing but the entry's shape can be what stops the read
        $this->writeStoredValue($objectId, $groupId, $keyId, [[
            'element' => ['type' => 'object', 'id' => $objectId],
            'fieldname' => 'keyDefinition',
            'columns' => ['quantity'],
            'data' => ['quantity' => 3],
        ]]);

        $this->switchKeyTo($keyId, new ManyToManyObjectRelation());

        $this->assertSame([], $this->readKey($objectId, $groupId, $keyId));
    }

    /**
     * And an advanced key reading its OWN entries is untouched by that distinction, so the fix is
     * "each shape belongs to one side" rather than "nested entries are refused".
     */
    public function testAnAdvancedRelationStillReadsItsOwnEntries(): void
    {
        [$groupId, $keyId, $objectId] = $this->storeAList('advancedKeepsItsOwn');

        $this->writeStoredValue($objectId, $groupId, $keyId, [[
            'element' => ['type' => 'object', 'id' => $objectId],
            'fieldname' => 'keyDefinition',
            'columns' => ['quantity'],
            'data' => ['quantity' => 3],
        ]]);

        $this->switchKeyTo($keyId, new AdvancedManyToManyObjectRelation());

        $items = $this->readKey($objectId, $groupId, $keyId);

        $this->assertIsArray($items);
        $this->assertCount(1, $items, 'the entry the advanced relation wrote is the one it reads');
    }

    /**
     * Rewrites the stored value of one key in the document, which is how a value written under
     * another type reaches a read: the entry is the one the engine wrote, with its `d` member
     * replaced.
     */
    private function writeStoredValue(int $objectId, int $groupId, int $keyId, mixed $value): void
    {
        $class = ClassDefinition::getByName('csstore');
        $this->assertNotNull($class);

        $table = 'object_classificationstore_doc_' . $class->getId();

        $stored = (string) Db::get()->fetchOne(
            'SELECT `values` FROM ' . $table . ' WHERE id = ? AND fieldname = ? AND groupId = ?',
            [$objectId, 'csstore', $groupId]
        );

        $document = json_decode($stored, true);
        $this->assertIsArray($document);

        $document['k'][$keyId] = [['d' => $value]];

        // `values` is a reserved word, so the column is named in a statement of its own rather than
        // through the query builder's unquoted update
        Db::get()->executeStatement(
            'UPDATE ' . $table . ' SET `values` = ? WHERE id = ? AND fieldname = ? AND groupId = ?',
            [json_encode($document, JSON_THROW_ON_ERROR), $objectId, 'csstore', $groupId]
        );

        $this->clearDefinitionCaches();
    }
}
