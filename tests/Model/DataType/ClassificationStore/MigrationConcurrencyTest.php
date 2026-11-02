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
                fn (Connection $other): mixed => $other->insert($this->getDocumentTable(), [
                    'id' => $objectId,
                    'fieldname' => self::FIELDNAME,
                    'groupId' => $this->getGroupId(),
                    'collectionId' => null,
                    'values' => '{}',
                    'checksum' => sha1('{}'),
                ])
            );
        } finally {
            $db->rollBack();
        }
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
        $other->insert($this->getDocumentTable(), [
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
