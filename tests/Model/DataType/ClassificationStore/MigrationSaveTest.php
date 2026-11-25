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

use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\DrainOutcome;
use Pimcore\Db;

/**
 * What an ordinary save does on the document engine: it writes the pair's documents, stamps their
 * checksum and takes the pair's EAV rows with it, in one transaction - so an object a user saves
 * while the drain is running is converted by that save.
 *
 * @group model.datatype.classificationstore
 */
class MigrationSaveTest extends AbstractMigrationTest
{
    /**
     * A field name the class does not carry any more, standing for the rows of another field of the
     * same object: the delete is per (objectId, fieldname) and must not reach them.
     */
    private const OTHER_FIELDNAME = 'csOther';

    private const MIGRATED_AT = 1762000000;

    private const VALUE_BEFORE = 'written on eav';

    private const VALUE_AFTER = 'written as a document';

    /**
     * The save writes the documents and deletes the pair's rows from both EAV tables, without
     * comparing anything first and without stamping migratedAt - that stamp is the drain's.
     */
    public function testASaveWritesTheDocumentsAndDeletesThePairsEavRows(): void
    {
        $object = $this->createObjectWithValue(self::INPUT_KEY, self::VALUE_BEFORE, 'save-migrates');
        $objectId = (int) $object->getId();

        $this->seedOtherFieldRows($objectId);
        $otherDataRows = $this->countRows($this->getDataTable(), self::OTHER_FIELDNAME);
        $otherGroupRows = $this->countRows($this->getGroupsTable(), self::OTHER_FIELDNAME);
        $this->assertSame(1, $otherDataRows);
        $this->assertSame(1, $otherGroupRows);

        $this->flipStore();

        $reloaded = $this->reload($objectId);
        $reloaded->getCsstore()->setLocalizedKeyValue(
            $this->getGroupId(),
            $this->getKeyId(self::INPUT_KEY),
            self::VALUE_AFTER
        );
        $reloaded->save();

        $rows = $this->readDocumentRows($objectId);
        $this->assertCount(1, $rows);
        $this->assertSame(0, $this->countRows($this->getDataTable(), self::FIELDNAME));
        $this->assertSame(0, $this->countRows($this->getGroupsTable(), self::FIELDNAME));

        // the rows of the other field name stay where they are
        $this->assertSame($otherDataRows, $this->countRows($this->getDataTable(), self::OTHER_FIELDNAME));
        $this->assertSame($otherGroupRows, $this->countRows($this->getGroupsTable(), self::OTHER_FIELDNAME));

        $this->assertNull($rows[0]['migratedAt']);
        $this->assertSame(sha1((string) $rows[0]['values']), (string) $rows[0]['checksum']);
    }

    /**
     * A store born on this engine never had the two EAV tables, and the cleanup of a converted
     * class drops them. The delete is then a no-op rather than an error.
     */
    public function testASaveSucceedsWhenTheEavTablesAreGone(): void
    {
        $object = $this->createObjectWithValue(self::INPUT_KEY, self::VALUE_BEFORE, 'save-without-eav');
        $objectId = (int) $object->getId();

        $this->flipStore();

        // the pair is converted first, so that dropping the tables does not drop values with them
        $reloaded = $this->reload($objectId);
        $reloaded->getCsstore()->setLocalizedKeyValue($this->getGroupId(), $this->getKeyId(self::INPUT_KEY), 'first');
        $reloaded->save();

        $this->dropTable($this->getDataTable());
        $this->dropTable($this->getGroupsTable());

        $reloaded = $this->reload($objectId);
        $reloaded->getCsstore()->setLocalizedKeyValue($this->getGroupId(), $this->getKeyId(self::INPUT_KEY), 'second');
        $reloaded->save();

        $rows = $this->readDocumentRows($objectId);
        $this->assertCount(1, $rows);
        $this->assertStringContainsString('second', (string) $rows[0]['values']);
    }

    /**
     * Every writer stamps the checksum; only the drain stamps migratedAt, so a document carrying
     * none was created by a user save. A save that writes nothing leaves both as they were.
     */
    public function testTheDrainStampsBothAndAnUnchangedSaveStampsNothing(): void
    {
        $object = $this->createObjectWithValue(self::INPUT_KEY, self::VALUE_BEFORE, 'save-stamps');
        $objectId = (int) $object->getId();

        $this->flipStore();

        $result = $this->getMigrationService()->drainPair($this->getClass(), $objectId, self::FIELDNAME, self::MIGRATED_AT);
        $this->assertSame(DrainOutcome::Converted, $result->outcome);

        $drained = $this->readDocumentRows($objectId);
        $this->assertCount(1, $drained);
        $this->assertSame(sha1((string) $drained[0]['values']), (string) $drained[0]['checksum']);
        $this->assertSame(self::MIGRATED_AT, (int) $drained[0]['migratedAt']);

        // a load-then-save that changed nothing writes nothing, so the stamps stay as the drain
        // left them
        $reloaded = $this->reload($objectId);
        $this->assertSame(self::VALUE_BEFORE, $reloaded->getCsstore()->getLocalizedKeyValue(
            $this->getGroupId(),
            $this->getKeyId(self::INPUT_KEY)
        ));
        $reloaded->save();

        $this->assertSame($drained, $this->readDocumentRows($objectId));
    }

    /**
     * A save that deactivated every group of a pair the read-through was still serving writes no
     * document at all, and has to take the pair's EAV rows all the same - they would otherwise be
     * read again on the next load and bring the deactivated groups back.
     */
    public function testDeactivatingEveryGroupOfAnUnconvertedPairStillTakesItsEavRows(): void
    {
        $object = $this->createObjectWithValue(self::INPUT_KEY, self::VALUE_BEFORE, 'save-deactivates');
        $objectId = (int) $object->getId();

        $this->flipStore();

        $reloaded = $this->reload($objectId);
        $reloaded->getCsstore()->setActiveGroups([]);
        $reloaded->save();

        $this->assertSame([], $this->readDocumentRows($objectId));
        $this->assertSame(0, $this->countRows($this->getDataTable(), self::FIELDNAME));
        $this->assertSame(0, $this->countRows($this->getGroupsTable(), self::FIELDNAME));

        $this->assertSame([], $this->reload($objectId)->getCsstore()->getActiveGroups());
    }

    /**
     * A row an earlier release wrote carries no checksum, and the save that rewrites it stamps one
     * without clearing the migratedAt of a drained row.
     */
    public function testASaveStampsAChecksumOnARowThatCarriedNoneAndKeepsTheMigrationStamp(): void
    {
        $object = $this->createObjectWithValue(self::INPUT_KEY, self::VALUE_BEFORE, 'save-restamps');
        $objectId = (int) $object->getId();

        $this->flipStore();
        $this->getMigrationService()->drainPair($this->getClass(), $objectId, self::FIELDNAME, self::MIGRATED_AT);

        Db::get()->update($this->getDocumentTable(), ['checksum' => null], ['id' => $objectId]);

        $reloaded = $this->reload($objectId);
        $reloaded->getCsstore()->setLocalizedKeyValue(
            $this->getGroupId(),
            $this->getKeyId(self::INPUT_KEY),
            self::VALUE_AFTER
        );
        $reloaded->save();

        $rows = $this->readDocumentRows($objectId);
        $this->assertCount(1, $rows);
        $this->assertSame(sha1((string) $rows[0]['values']), (string) $rows[0]['checksum']);
        $this->assertSame(self::MIGRATED_AT, (int) $rows[0]['migratedAt']);
    }

    /**
     * A group activation row and one value row under a field name of their own, so that the per
     * pair delete has something to leave alone.
     */
    private function seedOtherFieldRows(int $objectId): void
    {
        $db = Db::get();
        $groupId = $this->getGroupId(self::FIRST_GROUP);

        $db->insert($this->getGroupsTable(), [
            'id' => $objectId,
            'groupId' => $groupId,
            'fieldname' => self::OTHER_FIELDNAME,
        ]);
        $db->insert($this->getDataTable(), [
            'id' => $objectId,
            'groupId' => $groupId,
            'keyId' => $this->getKeyId('date'),
            'fieldname' => self::OTHER_FIELDNAME,
            'language' => 'default',
            'type' => 'date',
            'value' => '1762000000',
        ]);
    }
}
