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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\RetryableException;
use Pimcore;
use Pimcore\Db;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\Engine\DocumentEngine;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Concrete;
use Throwable;

/**
 * What the two sides of a conversion look like to a second connection: the lock the drain takes
 * before it reads anything else, and the one snapshot a load decides its source and reads it in.
 *
 * Both are about the order of statements within one transaction rather than about values, which is
 * the one thing a single-connection test cannot show.
 *
 * @group model.datatype.classificationstore
 */
class MigrationConcurrencyTest extends AbstractMigrationTest
{
    private const VALUE = 'written on eav';

    private const LOCK_WAIT_SECONDS = 1;

    private ?Connection $otherConnection = null;

    protected function tearDown(): void
    {
        $this->otherConnection?->close();
        $this->otherConnection = null;

        parent::tearDown();
    }

    /**
     * The drain asks whether the pair holds documents before it reads the EAV side, and that
     * question is a locking read: a writer on the same pair has to wait for the drain's transaction
     * rather than commit inside it. Were the question answered without the lock, the drain's
     * consistent-read snapshot would be established first and could be older than the state the
     * answer described - which is how a pair a user had just cleared came back from the EAV rows
     * that snapshot still showed.
     */
    public function testThePrecheckHoldsAPairThatHoldsDocuments(): void
    {
        $objectId = $this->createConvertedObject('concurrency-locked');

        $db = Db::get();
        $db->beginTransaction();

        try {
            $this->assertTrue(
                $this->documentEngine()->hasDocuments($this->getClassId(), $objectId, self::FIELDNAME)
            );

            $this->assertHeldAgainstTheOtherConnection(
                fn (Connection $other): mixed => $other->executeStatement(
                    'UPDATE ' . $other->quoteIdentifier($this->getDocumentTable())
                    . ' SET `collectionId` = 1 WHERE `id` = ? AND `fieldname` = ?',
                    [$objectId, self::FIELDNAME]
                )
            );
        } finally {
            $db->rollBack();
        }
    }

    /**
     * And a pair that holds none is held as well: what a converting writer would insert goes in the
     * gap those rows would occupy, so it has to wait there too.
     *
     * This half needs REPEATABLE READ, which is the isolation the whole write path of the document
     * engine is written against. A server running READ COMMITTED has no gap to lock, and that is
     * skipped rather than reported as a failure of this code.
     */
    public function testThePrecheckHoldsAnEmptyPairAgainstAConcurrentInsert(): void
    {
        $this->skipUnlessRepeatableRead();

        $objectId = (int) $this->createObjectWithValue(self::INPUT_KEY, self::VALUE, 'concurrency-gap')->getId();
        $this->flipStore();

        $db = Db::get();
        $db->beginTransaction();

        try {
            $this->assertFalse(
                $this->documentEngine()->hasDocuments($this->getClassId(), $objectId, self::FIELDNAME)
            );

            $this->assertHeldAgainstTheOtherConnection(
                function (Connection $other) use ($objectId): void {
                    $this->insertRow($other, $this->getDocumentTable(), [
                        'id' => $objectId,
                        'fieldname' => self::FIELDNAME,
                        'groupId' => $this->getGroupId(),
                        'collectionId' => null,
                        'values' => '{}',
                        'checksum' => sha1('{}'),
                    ]);
                }
            );
        } finally {
            $db->rollBack();
        }
    }

    /**
     * The drain and an ordinary save take the object's row and the pair's document rows in the same
     * order, which is what keeps them from deadlocking on each other.
     *
     * A save locks the object's row first - {@see \Pimcore\Model\DataObject\AbstractObject::save()}
     * through `getCurrentFullPathForUpdate()` - and the pair's documents second. The drain asks
     * whether the pair holds documents before it writes, and its insert then needs the object's row
     * for the foreign key of the document table: taken in that order the two would deadlock, the
     * save waiting on the document gap the drain holds while the drain waits on the object row the
     * save holds. The drain therefore takes the object's row itself, first.
     *
     * What that lock is is what this asserts: an *exclusive* lock on the object's row. The foreign
     * key of the insert takes a shared one, which a second shared reader would be let past - so a
     * second connection that cannot read the row in share mode is one the drain is holding
     * deliberately rather than as a side effect of its own insert.
     */
    public function testTheDrainHoldsTheObjectRowItselfRatherThanThroughItsForeignKey(): void
    {
        $objectId = (int) $this->createObjectWithValue(self::INPUT_KEY, self::VALUE, 'concurrency-order')->getId();
        $this->flipStore();

        $db = Db::get();
        $db->beginTransaction();

        try {
            $this->getMigrationService()->drainPair($this->getClass(), $objectId, self::FIELDNAME, time());

            $this->assertHeldAgainstTheOtherConnection(
                // LOCK IN SHARE MODE rather than FOR SHARE: the older spelling is the one both
                // MySQL and MariaDB accept
                fn (Connection $other): mixed => $other->fetchOne(
                    'SELECT `id` FROM `objects` WHERE `id` = ? LOCK IN SHARE MODE',
                    [$objectId]
                )
            );

            // and the pair's documents are held as well, so the whole conversion is one unit
            $this->assertHeldAgainstTheOtherConnection(
                fn (Connection $other): mixed => $other->executeStatement(
                    'UPDATE ' . $other->quoteIdentifier($this->getDocumentTable())
                    . ' SET `collectionId` = 1 WHERE `id` = ? AND `fieldname` = ?',
                    [$objectId, self::FIELDNAME]
                )
            );
        } finally {
            $db->rollBack();
        }
    }

    /**
     * And from the save's side: while the object's row is held the way a save holds it, the drain
     * waits there instead of racing ahead, and the pair it could not convert is left wholly on EAV.
     */
    public function testADrainWaitsForTheObjectRowAnotherWriterHolds(): void
    {
        $objectId = (int) $this->createObjectWithValue(self::INPUT_KEY, self::VALUE, 'concurrency-wait')->getId();
        $this->flipStore();

        $eavRows = $this->countRows($this->getDataTable(), self::FIELDNAME);
        $this->assertGreaterThan(0, $eavRows);

        $other = $this->otherConnection();
        $other->beginTransaction();

        $db = Db::get();
        $db->executeStatement('SET SESSION innodb_lock_wait_timeout = ' . self::LOCK_WAIT_SECONDS);

        try {
            // the statement a save takes the object's row with, issued by the other connection
            $other->fetchOne(
                'SELECT CONCAT(`path`, `key`) FROM objects WHERE id = ? FOR UPDATE',
                [$objectId]
            );

            try {
                $this->getMigrationService()->drainPair($this->getClass(), $objectId, self::FIELDNAME, time());
                $this->fail('the drain has to wait for the object row the other writer holds');
            } catch (RetryableException) {
                // a lock wait timeout or a deadlock: either way the drain had to wait there
            }
        } finally {
            $other->rollBack();
            $db->executeStatement('SET SESSION innodb_lock_wait_timeout = DEFAULT');
        }

        $this->assertSame([], $this->readDocumentRows($objectId), 'and wrote no document');
        $this->assertSame($eavRows, $this->countRows($this->getDataTable(), self::FIELDNAME));
    }

    /**
     * A conversion commits the pair's documents and the deletion of its EAV rows together, so a
     * load that chose its source by one autocommit read and loaded it by the next could fall
     * between those two states and answer with neither. The source of an unconverted pair is
     * therefore chosen and read inside one transaction, which is what this counts: the load of a
     * pair that reads through opens one, the load of a converted pair needs none.
     */
    public function testTheSourceOfAnUnconvertedPairIsChosenAndReadInOneTransaction(): void
    {
        $this->skipUnlessCommitsAreCounted();

        $objectId = (int) $this->createObjectWithValue(
            self::INPUT_KEY,
            self::VALUE,
            'concurrency-snapshot'
        )->getId();
        $this->flipStore();

        $object = $this->concrete($objectId);
        $readThroughStore = $this->buildStore($object);
        $documentStore = $this->buildStore($object);
        $engine = $this->documentEngine();

        $before = $this->countCommits();
        $engine->load($readThroughStore);
        $this->assertGreaterThan($before, $this->countCommits(), 'the read-through needs one snapshot');
        $this->assertSame(self::VALUE, $this->valueOf($readThroughStore));

        $this->getMigrationService()->drainPair($this->getClass(), $objectId, self::FIELDNAME, time());

        $before = $this->countCommits();
        $engine->load($documentStore);
        $this->assertSame($before, $this->countCommits(), 'a converted pair is answered by one read');
        $this->assertSame(self::VALUE, $this->valueOf($documentStore));
    }

    /**
     * And what that snapshot buys: a load is either wholly on the side the conversion came from or
     * wholly on the side it went to, never on neither. The conversion is committed by a second
     * connection here, so the committing transaction is genuinely not the reading one.
     */
    public function testALoadNeverFallsBetweenTheTwoSidesOfAConversion(): void
    {
        // both objects are written while the store is still on EAV, so both pairs start unconverted
        $donorId = (int) $this->createObjectWithValue(self::INPUT_KEY, self::VALUE, 'concurrency-donor')->getId();
        $objectId = (int) $this->createObjectWithValue(self::INPUT_KEY, self::VALUE, 'concurrency-commit')->getId();

        $this->flipStore();

        $document = $this->drainADonorAndTakeItsDocument($donorId);
        $object = $this->concrete($objectId);

        $other = $this->otherConnection();
        $other->beginTransaction();

        try {
            $this->convertOnTheOtherConnection($other, $objectId, $document);

            // the conversion is not committed, so this load is wholly on the side it came from
            $beforeTheCommit = $this->buildStore($object);
            $this->documentEngine()->load($beforeTheCommit);
            $this->assertSame(self::VALUE, $this->valueOf($beforeTheCommit));
            $this->assertSame([$this->getGroupId() => true], $beforeTheCommit->getActiveGroups());

            $other->commit();
        } catch (Throwable $exception) {
            if ($other->isTransactionActive()) {
                $other->rollBack();
            }

            throw $exception;
        }

        // and once it is, wholly on the side it went to - with the EAV rows that backed the read
        // above gone, so a load that still answered from them would answer with nothing
        $this->assertSame(0, $this->countRows($this->getDataTable(), self::FIELDNAME));

        $afterTheCommit = $this->buildStore($object);
        $this->documentEngine()->load($afterTheCommit);
        $this->assertSame(self::VALUE, $this->valueOf($afterTheCommit));
        $this->assertSame([$this->getGroupId() => true], $afterTheCommit->getActiveGroups());
    }

    /**
     * A document written by the engine itself, by draining an object kept for the purpose: the
     * payload the second connection writes has to be one the engine reads back, and guessing its
     * shape here would be testing this test's idea of it.
     */
    private function drainADonorAndTakeItsDocument(int $donorId): string
    {
        $this->getMigrationService()->drainPair($this->getClass(), $donorId, self::FIELDNAME, time());

        $rows = $this->readDocumentRows($donorId);
        $this->assertCount(1, $rows);
        $this->assertSame((string) $this->getGroupId(), (string) $rows[0]['groupId']);

        return (string) $rows[0]['values'];
    }

    /**
     * Writes the documents of the pair and deletes its EAV rows, which is what a conversion commits
     * as one unit - here on a connection of its own, without the model layer.
     */
    private function convertOnTheOtherConnection(Connection $other, int $objectId, string $document): void
    {
        $this->insertRow($other, $this->getDocumentTable(), [
            'id' => $objectId,
            'fieldname' => self::FIELDNAME,
            'groupId' => $this->getGroupId(),
            'collectionId' => null,
            'values' => $document,
            'checksum' => sha1($document),
            'migratedAt' => time(),
        ]);

        foreach ([$this->getDataTable(), $this->getGroupsTable()] as $table) {
            $other->delete($table, ['id' => $objectId, 'fieldname' => self::FIELDNAME]);
        }
    }

    /**
     * An object whose pair is converted, so that the precheck finds documents to lock.
     */
    private function createConvertedObject(string $key): int
    {
        $objectId = (int) $this->createObjectWithValue(self::INPUT_KEY, self::VALUE, $key)->getId();

        if ($this->getStoredEngine() !== self::DOCUMENT_ENGINE) {
            $this->flipStore();
        }

        $this->getMigrationService()->drainPair($this->getClass(), $objectId, self::FIELDNAME, time());
        $this->assertNotSame([], $this->readDocumentRows($objectId));

        return $objectId;
    }

    /**
     * A model built the way the migration builds one, so that what a load costs is the engine's own
     * statements and not an object load's.
     */
    private function buildStore(Concrete $object): Classificationstore
    {
        $classificationStore = new Classificationstore();
        $classificationStore->setObject($object);
        $classificationStore->setClass($this->getClass());
        $classificationStore->setFieldname(self::FIELDNAME);

        return $classificationStore;
    }

    private function concrete(int $objectId): Concrete
    {
        $object = Concrete::getById($objectId, ['force' => true]);
        $this->assertInstanceOf(Concrete::class, $object);

        return $object;
    }

    private function valueOf(Classificationstore $classificationStore): mixed
    {
        return $classificationStore->getLocalizedKeyValue(
            $this->getGroupId(),
            $this->getKeyId(self::INPUT_KEY)
        );
    }

    /**
     * Asserts that the given statement cannot get through while this test's transaction holds the
     * pair. The other connection is given a lock wait of its own, so a held pair fails the
     * statement rather than hanging the suite.
     *
     * @param callable(Connection): mixed $statement
     */
    private function assertHeldAgainstTheOtherConnection(callable $statement): void
    {
        $other = $this->otherConnection();
        $other->executeStatement('SET SESSION innodb_lock_wait_timeout = ' . self::LOCK_WAIT_SECONDS);

        try {
            $statement($other);
        } catch (RetryableException) {
            // a lock wait timeout or a deadlock: either way the other connection had to wait
            return;
        }

        $this->fail('the pair has to be held against the other connection');
    }

    /**
     * COMMIT statements this session has issued, which is how an opened and closed transaction is
     * told from a plain read.
     */
    private function countCommits(): int
    {
        $row = Db::get()->fetchAssociative("SHOW SESSION STATUS LIKE 'Com_commit'");

        return (int) ($row['Value'] ?? 0);
    }

    /**
     * Whether this driver reports its COMMIT in the session counters at all, so that a driver that
     * does not is skipped instead of failing the assertion the counter carries.
     */
    private function skipUnlessCommitsAreCounted(): void
    {
        $db = Db::get();
        $before = $this->countCommits();

        $db->beginTransaction();
        $db->commit();

        if ($this->countCommits() <= $before) {
            $this->markTestSkipped('this driver does not report its COMMIT in Com_commit');
        }
    }

    private function skipUnlessRepeatableRead(): void
    {
        $row = Db::get()->fetchAssociative("SHOW SESSION VARIABLES LIKE '%isolation'");
        $level = strtoupper(str_replace('-', ' ', (string) ($row['Value'] ?? '')));

        if ($level !== 'REPEATABLE READ') {
            $this->markTestSkipped('gap locking is a REPEATABLE READ property; this server runs ' . $level);
        }
    }

    private function documentEngine(): DocumentEngine
    {
        $resolver = Pimcore::getContainer()->get(EngineResolver::class);
        $this->assertInstanceOf(EngineResolver::class, $resolver);

        $engine = $resolver->getEngines()[self::DOCUMENT_ENGINE] ?? null;
        $this->assertInstanceOf(DocumentEngine::class, $engine);

        return $engine;
    }

    /**
     * A second connection with the parameters of the shared one, for the writer the tests above
     * have to be interleaved with.
     */
    private function otherConnection(): Connection
    {
        if ($this->otherConnection === null) {
            $this->otherConnection = DriverManager::getConnection(Db::get()->getParams());
        }

        return $this->otherConnection;
    }
}
