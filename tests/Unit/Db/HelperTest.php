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

namespace Pimcore\Tests\Unit\Db;

use Doctrine\DBAL\Connection;
use LogicException;
use PDO;
use Pimcore\Db;
use Pimcore\Db\Helper;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Tests for Db\Helper::updateOrInsert() and the legacy Db\Helper::upsert() it falls back to.
 *
 * The return value is the delicate part: callers such as Notification\Dao and Element\Note\Dao
 * take it as the id of the freshly created row, so an insert has to return the generated id while
 * an update has to return null - anything else silently assigns a wrong id to the model.
 *
 * @internal
 */
final class HelperTest extends TestCase
{
    private const TABLE_AUTO_INCREMENT = 'test_upsert_auto_increment';

    private const TABLE_COMPOSITE_KEY = 'test_upsert_composite_key';

    private const TABLE_TRIGGER_LOG = 'test_upsert_trigger_log';

    private const TABLE_LAST_INSERT_ID_LOG = 'test_upsert_last_insert_id_log';

    protected bool $cleanupDbInSetup = false;

    private Connection $db;

    protected function needsDb(): bool
    {
        return true;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = Db::get();

        $this->dropTables();

        // an auto increment primary key plus a second unique key, as objects/assets/documents have
        $this->db->executeStatement(
            'CREATE TABLE ' . self::TABLE_AUTO_INCREMENT . ' (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(50) NOT NULL,
                `value` varchar(50) DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `name` (`name`)
            ) DEFAULT CHARSET=utf8mb4'
        );

        // no auto increment at all, as properties/translations/element_workflow_state have,
        // including a column whose name needs quoting
        $this->db->executeStatement(
            'CREATE TABLE ' . self::TABLE_COMPOSITE_KEY . ' (
                `cid` int(11) NOT NULL,
                `ctype` varchar(20) NOT NULL,
                `key` varchar(50) DEFAULT NULL,
                PRIMARY KEY (`cid`, `ctype`)
            ) DEFAULT CHARSET=utf8mb4'
        );
    }

    protected function tearDown(): void
    {
        $this->dropTables();

        parent::tearDown();
    }

    public function testInsertReturnsTheGeneratedId(): void
    {
        // a new model carries no id yet, exactly as Note\Dao and Version\Dao pass it
        $lastInsertId = Helper::updateOrInsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'first', 'value' => 'inserted'],
            ['id']
        );

        $this->assertNotNull($lastInsertId, 'The insert path has to return the generated id.');

        $row = $this->fetchRowByName('first');
        $this->assertSame((int) $lastInsertId, (int) $row['id'], 'The returned id must be the id of the inserted row.');
        $this->assertSame('inserted', $row['value']);
    }

    public function testInsertOfAMissingRowWithAKnownIdReturnsTheId(): void
    {
        // the UPDATE matches nothing, the token is not seen, the insert goes through upsert()
        $lastInsertId = Helper::updateOrInsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => 42, 'name' => 'first', 'value' => 'inserted'],
            ['id']
        );

        $this->assertSame(42, (int) $lastInsertId);
        $this->assertSame('inserted', $this->fetchRowByName('first')['value']);
    }

    public function testUpdateReturnsNullAndUpdatesTheRow(): void
    {
        $id = (int) Helper::updateOrInsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'first', 'value' => 'inserted'],
            ['id']
        );

        $lastInsertId = Helper::updateOrInsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => $id, 'name' => 'first', 'value' => 'updated'],
            ['id']
        );

        // null and not 0: callers only skip assigning the id if this is null
        $this->assertNull($lastInsertId, 'The update path must not return an id.');

        $row = $this->fetchRowByName('first');
        $this->assertSame($id, (int) $row['id'], 'The update must not change the id of the row.');
        $this->assertSame('updated', $row['value']);
        $this->assertSame(1, $this->countRows(self::TABLE_AUTO_INCREMENT), 'The update must not insert a second row.');
    }

    public function testUpdateWithUnchangedValuesReturnsNull(): void
    {
        $data = ['id' => 1, 'name' => 'first', 'value' => 'unchanged'];
        Helper::updateOrInsert($this->db, self::TABLE_AUTO_INCREMENT, $data, ['id']);

        // the UPDATE changes nothing, but it matched - the token tells, and no insert is tried
        $lastInsertId = Helper::updateOrInsert($this->db, self::TABLE_AUTO_INCREMENT, $data, ['id']);

        $this->assertNull($lastInsertId, 'An update that changes nothing must not return an id either.');
        $this->assertSame(1, $this->countRows(self::TABLE_AUTO_INCREMENT));
    }

    public function testInsertAfterAnUpdateReturnsTheNewId(): void
    {
        $firstId = (int) Helper::updateOrInsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'first', 'value' => 'inserted'],
            ['id']
        );

        Helper::updateOrInsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => $firstId, 'name' => 'first', 'value' => 'updated'],
            ['id']
        );

        $secondId = Helper::updateOrInsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'second', 'value' => 'inserted'],
            ['id']
        );

        $this->assertNotNull($secondId);
        $this->assertNotSame($firstId, (int) $secondId, 'An insert following an update must return the new id.');
        $this->assertSame((int) $secondId, (int) $this->fetchRowByName('second')['id']);
    }

    public function testOnATableWithoutAutoIncrement(): void
    {
        $data = ['cid' => 5, 'ctype' => 'object', 'key' => 'inserted'];
        $keys = ['cid', 'ctype'];

        $insertResult = Helper::updateOrInsert($this->db, self::TABLE_COMPOSITE_KEY, $data, $keys);
        // there is no auto increment column, so there is no id to report
        $this->assertSame(0, (int) $insertResult);

        $data['key'] = 'updated';
        $updateResult = Helper::updateOrInsert($this->db, self::TABLE_COMPOSITE_KEY, $data, $keys);

        $this->assertNull($updateResult, 'The update path must not return an id.');
        $this->assertSame(1, $this->countRows(self::TABLE_COMPOSITE_KEY));
        $this->assertSame(
            'updated',
            $this->db->fetchOne('SELECT `key` FROM ' . self::TABLE_COMPOSITE_KEY . ' WHERE cid = 5')
        );
    }

    public function testWithoutQuotedIdentifiers(): void
    {
        $data = ['cid' => 7, 'ctype' => 'asset'];
        $keys = ['cid', 'ctype'];

        Helper::updateOrInsert($this->db, self::TABLE_COMPOSITE_KEY, $data, $keys, false);
        $updateResult = Helper::updateOrInsert($this->db, self::TABLE_COMPOSITE_KEY, $data, $keys, false);

        $this->assertNull($updateResult);
        $this->assertSame(1, $this->countRows(self::TABLE_COMPOSITE_KEY));
    }

    public function testTakesTheTableNameAsGivenLikeUpsert(): void
    {
        // $quoteIdentifiers covers the column names only; the table is used as given, as by
        // upsert() and DBAL's insert()/update() - so a name passed already quoted works on the
        // UPDATE and on the upsert() fallback alike (blanket quoting would break it)
        $table = $this->db->quoteIdentifier(self::TABLE_COMPOSITE_KEY);
        $keys = ['cid', 'ctype'];

        Helper::updateOrInsert($this->db, $table, ['cid' => 17, 'ctype' => 'object', 'key' => 'inserted'], $keys);
        $result = Helper::updateOrInsert($this->db, $table, ['cid' => 17, 'ctype' => 'object', 'key' => 'updated'], $keys);

        $this->assertNull($result);
        $this->assertSame(1, $this->countRows(self::TABLE_COMPOSITE_KEY));
        $this->assertSame(
            'updated',
            $this->db->fetchOne('SELECT `key` FROM ' . self::TABLE_COMPOSITE_KEY . ' WHERE cid = 17')
        );
    }

    public function testMissingKeyThrowsOnADuplicateWithoutWriting(): void
    {
        $data = ['cid' => 9, 'ctype' => 'document', 'key' => 'inserted'];
        Helper::updateOrInsert($this->db, self::TABLE_COMPOSITE_KEY, $data, ['cid', 'ctype']);

        $caught = null;

        try {
            // a missing key skips the UPDATE; upsert()'s failing INSERT then reports the misuse
            $data['key'] = 'changed';
            Helper::updateOrInsert($this->db, self::TABLE_COMPOSITE_KEY, $data, ['cid', 'missing']);
            $this->fail('Expected LogicException was not thrown.');
        } catch (LogicException $e) {
            $caught = $e;
        }

        $this->assertSame('Key "`missing`" passed for upsert not found in data', $caught->getMessage());
        // the failed insert leaves no trace, so nothing was written
        $this->assertSame(
            'inserted',
            $this->db->fetchOne('SELECT `key` FROM ' . self::TABLE_COMPOSITE_KEY . ' WHERE cid = 9')
        );
    }

    public function testInsertWithMissingKeySucceedsWithoutConflict(): void
    {
        // BC pin: upsert() reads $keys only after a duplicate, so an insert that does not collide
        // succeeds even when a listed key is absent from $data
        Helper::updateOrInsert(
            $this->db,
            self::TABLE_COMPOSITE_KEY,
            ['cid' => 11, 'ctype' => 'object', 'key' => 'inserted'],
            ['cid', 'missing']
        );

        $this->assertSame(
            'inserted',
            $this->db->fetchOne('SELECT `key` FROM ' . self::TABLE_COMPOSITE_KEY . ' WHERE cid = 11')
        );
    }

    public function testEmptyKeysInsertViaTheLegacyPath(): void
    {
        // BC pin: AbstractDao::getPrimaryKey() returns [] for a table without a primary key
        Helper::updateOrInsert(
            $this->db,
            self::TABLE_COMPOSITE_KEY,
            ['cid' => 13, 'ctype' => 'object', 'key' => 'inserted'],
            []
        );

        $this->assertSame(
            'inserted',
            $this->db->fetchOne('SELECT `key` FROM ' . self::TABLE_COMPOSITE_KEY . ' WHERE cid = 13')
        );
    }

    public function testKeyColumnsAreWrittenOnTheUpdatePath(): void
    {
        // upsert()'s UPDATE writes all of $data including the key columns, so a key value that
        // compares equal but is stored differently - here under the case-insensitive collation of
        // the test table - is updated to the incoming representation; the single UPDATE does the same
        Helper::updateOrInsert(
            $this->db,
            self::TABLE_COMPOSITE_KEY,
            ['cid' => 15, 'ctype' => 'object', 'key' => 'inserted'],
            ['cid', 'ctype']
        );

        $result = Helper::updateOrInsert(
            $this->db,
            self::TABLE_COMPOSITE_KEY,
            ['cid' => 15, 'ctype' => 'OBJECT', 'key' => 'updated'],
            ['cid', 'ctype']
        );

        $this->assertNull($result, 'The update path must not return an id.');
        $this->assertSame(1, $this->countRows(self::TABLE_COMPOSITE_KEY));
        $this->assertSame(
            ['ctype' => 'OBJECT', 'key' => 'updated'],
            $this->db->fetchAssociative('SELECT `ctype`, `key` FROM ' . self::TABLE_COMPOSITE_KEY . ' WHERE cid = 15'),
            'The stored key representation must follow the incoming data.'
        );
    }

    public function testNullKeyWithNonKeyUniqueConflictThrowsWithoutWriting(): void
    {
        $id = (int) Helper::updateOrInsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'first', 'value' => 'inserted'],
            ['id']
        );

        try {
            // null id + unique `name` conflict: a null key skips the UPDATE and takes the upsert()
            // path, whose failing INSERT writes nothing and whose WHERE clause reports the misuse
            Helper::updateOrInsert(
                $this->db,
                self::TABLE_AUTO_INCREMENT,
                ['id' => null, 'name' => 'first', 'value' => 'hijacked'],
                ['id']
            );
            $this->fail('Expected LogicException was not thrown.');
        } catch (LogicException $e) {
            $this->assertStringContainsString('passed for upsert not found in data', $e->getMessage());
        }

        $row = $this->fetchRowByName('first');
        $this->assertSame($id, (int) $row['id']);
        $this->assertSame('inserted', $row['value'], 'The conflicting row must not be modified.');
        $this->assertSame(1, $this->countRows(self::TABLE_AUTO_INCREMENT));
    }

    public function testConflictOnNonKeyUniqueIndexWithAnExistingKeyedRowThrowsWithoutWriting(): void
    {
        $firstId = (int) Helper::updateOrInsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'first', 'value' => 'inserted'],
            ['id']
        );
        $secondId = (int) Helper::updateOrInsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'second', 'value' => 'inserted'],
            ['id']
        );

        try {
            // the keyed row exists AND the new values collide with another row's unique `name`:
            // the UPDATE ... WHERE id violates the unique index, as upsert()'s UPDATE did
            Helper::updateOrInsert(
                $this->db,
                self::TABLE_AUTO_INCREMENT,
                ['id' => $secondId, 'name' => 'first', 'value' => 'hijacked'],
                ['id']
            );
            $this->fail('Expected UniqueConstraintViolationException was not thrown.');
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
            // the expected outcome, as with upsert(); what matters is verified below - neither row
            // was written
        }

        $this->assertSame(2, $this->countRows(self::TABLE_AUTO_INCREMENT));
        $this->assertSame($firstId, (int) $this->fetchRowByName('first')['id']);
        $this->assertSame('inserted', $this->fetchRowByName('second')['value'], 'The keyed row must not be partially written.');
    }

    public function testNeverTouchesARowConflictingOnAnotherUniqueIndex(): void
    {
        $this->createTriggerLog();
        $this->db->executeStatement(
            'CREATE TRIGGER test_upsert_before_update BEFORE UPDATE ON ' . self::TABLE_AUTO_INCREMENT
            . ' FOR EACH ROW INSERT INTO ' . self::TABLE_TRIGGER_LOG . " VALUES ('before_update', OLD.id)"
        );

        $id = (int) Helper::updateOrInsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'first', 'value' => 'inserted'],
            ['id']
        );

        // a different id colliding with the existing row's unique `name`: the UPDATE ... WHERE id
        // matches no row, the insert fails on `name`, and upsert()'s UPDATE matches no row either -
        // so no trigger runs on the foreign row, exactly as before
        $result = Helper::updateOrInsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => $id + 1000, 'name' => 'first', 'value' => 'hijacked'],
            ['id']
        );

        $this->assertNull($result);
        $this->assertSame(1, $this->countRows(self::TABLE_AUTO_INCREMENT));
        $this->assertSame(0, (int) $this->db->fetchOne('SELECT COUNT(*) FROM ' . self::TABLE_TRIGGER_LOG), 'No UPDATE trigger runs on the foreign row.');

        $row = $this->fetchRowByName('first');
        $this->assertSame($id, (int) $row['id']);
        $this->assertSame('inserted', $row['value']);
    }

    public function testRunsTheUpdateTriggersOnceForAnUnchangedRow(): void
    {
        $this->createTriggerLog();
        $this->db->executeStatement(
            'CREATE TRIGGER test_upsert_before_update BEFORE UPDATE ON ' . self::TABLE_AUTO_INCREMENT
            . ' FOR EACH ROW INSERT INTO ' . self::TABLE_TRIGGER_LOG . " VALUES ('before_update', OLD.id)"
        );
        $this->db->executeStatement(
            'CREATE TRIGGER test_upsert_before_insert BEFORE INSERT ON ' . self::TABLE_AUTO_INCREMENT
            . ' FOR EACH ROW INSERT INTO ' . self::TABLE_TRIGGER_LOG . " VALUES ('before_insert', IFNULL(NEW.id, 0))"
        );

        $data = ['id' => 1, 'name' => 'first', 'value' => 'inserted'];
        Helper::updateOrInsert($this->db, self::TABLE_AUTO_INCREMENT, $data, ['id']);
        $this->db->executeStatement('DELETE FROM ' . self::TABLE_TRIGGER_LOG);

        // the UPDATE changes nothing but recorded its match, so neither an INSERT nor a second
        // UPDATE runs - the row's UPDATE triggers run exactly once, as with upsert()
        $this->assertNull(Helper::updateOrInsert($this->db, self::TABLE_AUTO_INCREMENT, $data, ['id']));

        $this->assertSame(
            ['before_update'],
            $this->db->fetchFirstColumn('SELECT `event` FROM ' . self::TABLE_TRIGGER_LOG),
            'An unchanged row runs its BEFORE UPDATE trigger once and nothing else.'
        );
        $this->assertSame(1, $this->countRows(self::TABLE_AUTO_INCREMENT));
    }

    public function testRunsTheUpdateTriggersOnceForARowATriggerNormalizes(): void
    {
        $this->createTriggerLog();
        // a BEFORE UPDATE trigger that logs and resets the incoming value: the UPDATE matches the
        // row but changes nothing, exactly like an unchanged row - and unlike a missing one
        $this->db->executeStatement(
            'CREATE TRIGGER test_upsert_normalizing BEFORE UPDATE ON ' . self::TABLE_AUTO_INCREMENT
            . ' FOR EACH ROW BEGIN'
            . ' INSERT INTO ' . self::TABLE_TRIGGER_LOG . " VALUES ('before_update', OLD.id);"
            . ' SET NEW.value = OLD.value;'
            . ' END'
        );

        $id = (int) Helper::updateOrInsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'first', 'value' => 'inserted'],
            ['id']
        );
        $this->db->executeStatement('DELETE FROM ' . self::TABLE_TRIGGER_LOG);

        $result = Helper::updateOrInsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => $id, 'name' => 'first', 'value' => 'rejected by the trigger'],
            ['id']
        );

        $this->assertNull($result, 'The row matched, so this is the update path.');
        $this->assertSame(
            ['before_update'],
            $this->db->fetchFirstColumn('SELECT `event` FROM ' . self::TABLE_TRIGGER_LOG),
            'The trigger runs once; the zero changed rows must not be mistaken for a missing row.'
        );
        $this->assertSame('inserted', $this->fetchRowByName('first')['value']);
        $this->assertSame(1, $this->countRows(self::TABLE_AUTO_INCREMENT));
    }

    public function testUpdateTriggersObserveTheMatchTokenInLastInsertId(): void
    {
        // the documented constraint: an update trigger reading LAST_INSERT_ID() sees the match
        // token, not the id of an earlier insert. The log table has an auto-increment column, so
        // each trigger's own INSERT changes LAST_INSERT_ID() while the trigger runs - the server
        // restores it when the trigger ends, which is what keeps the match detection intact
        $this->db->executeStatement(
            'CREATE TABLE ' . self::TABLE_LAST_INSERT_ID_LOG . ' (
                `n` int(11) NOT NULL AUTO_INCREMENT,
                `event` varchar(20) NOT NULL,
                `last_insert_id` bigint(20) unsigned NOT NULL,
                PRIMARY KEY (`n`)
            ) DEFAULT CHARSET=utf8mb4'
        );
        foreach (['before', 'after'] as $when) {
            $this->db->executeStatement(
                'CREATE TRIGGER test_upsert_' . $when . '_update ' . strtoupper($when) . ' UPDATE ON ' . self::TABLE_AUTO_INCREMENT
                . ' FOR EACH ROW INSERT INTO ' . self::TABLE_LAST_INSERT_ID_LOG
                . " (`event`, `last_insert_id`) VALUES ('" . $when . "_update', LAST_INSERT_ID())"
            );
        }

        $id = (int) Helper::updateOrInsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'first', 'value' => 'inserted'],
            ['id']
        );
        $this->assertSame((string) $id, (string) $this->db->fetchOne('SELECT LAST_INSERT_ID()'));

        $this->assertNull(Helper::updateOrInsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => $id, 'name' => 'first', 'value' => 'changed'],
            ['id']
        ));

        $observed = $this->db->fetchAllKeyValue(
            'SELECT `event`, `last_insert_id` FROM ' . self::TABLE_LAST_INSERT_ID_LOG . ' ORDER BY n'
        );
        $token = (string) $this->db->fetchOne('SELECT LAST_INSERT_ID()');
        $this->assertSame(['before_update', 'after_update'], array_keys($observed));
        $this->assertNotSame((string) $id, $token, 'The UPDATE replaces the last insert id with the match token.');
        $this->assertSame(
            $token,
            (string) $observed['before_update'],
            'A BEFORE UPDATE trigger sees the match token in LAST_INSERT_ID().'
        );
        $this->assertSame(
            $token,
            (string) $observed['after_update'],
            'An AFTER UPDATE trigger sees it too: the value is restored after the BEFORE UPDATE trigger\'s own insert.'
        );
        $this->db->executeStatement('DELETE FROM ' . self::TABLE_LAST_INSERT_ID_LOG);

        // an unchanged row: the triggers' own auto-increment inserts must not defeat the detection
        $this->assertNull(Helper::updateOrInsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => $id, 'name' => 'first', 'value' => 'changed'],
            ['id']
        ));
        $events = $this->db->fetchFirstColumn('SELECT `event` FROM ' . self::TABLE_LAST_INSERT_ID_LOG . ' ORDER BY n');
        $this->assertCount(
            1,
            array_keys($events, 'before_update', true),
            'The unchanged row was recognised as matched: its BEFORE UPDATE trigger ran once and no second UPDATE followed.'
        );
        $this->assertSame(1, $this->countRows(self::TABLE_AUTO_INCREMENT));
    }

    public function testAppliesTheDataToARowInsertedConcurrently(): void
    {
        // the interleaving: the UPDATE matches no row, then a second connection inserts the
        // keyed row before this connection can - reproduced deterministically by a connection
        // wrapper that performs that insert right after its own zero-row UPDATE; upsert()'s
        // duplicate handling then applies the data to that row
        $params = $this->db->getParams();
        $params['wrapperClass'] = ConcurrentInsertConnection::class;
        $connection = \Doctrine\DBAL\DriverManager::getConnection($params);
        $connection->other = $this->db;
        $connection->concurrentRow = ['id' => 7, 'name' => 'first', 'value' => 'concurrent'];

        try {
            $result = Helper::updateOrInsert(
                $connection,
                self::TABLE_AUTO_INCREMENT,
                ['id' => 7, 'name' => 'first', 'value' => 'ours'],
                ['id']
            );
        } finally {
            $connection->close();
        }

        $this->assertNull($result, 'The row existed by the time of the insert, so this is the update path.');
        $this->assertSame(1, $this->countRows(self::TABLE_AUTO_INCREMENT));
        $this->assertSame(
            'ours',
            $this->fetchRowByName('first')['value'],
            'The data must be applied to the concurrently inserted row, as upsert() did.'
        );
    }

    public function testOnAFoundRowsConnection(): void
    {
        // with CLIENT_FOUND_ROWS the UPDATE of an unchanged row reports 1 matched row, which is
        // equally correct: the row exists and holds the data
        $params = $this->db->getParams();
        $params['driverOptions'][PDO::MYSQL_ATTR_FOUND_ROWS] = true;
        $foundRowsConnection = \Doctrine\DBAL\DriverManager::getConnection($params);

        try {
            $id = (int) Helper::updateOrInsert(
                $foundRowsConnection,
                self::TABLE_AUTO_INCREMENT,
                ['id' => null, 'name' => 'found-rows', 'value' => 'inserted'],
                ['id']
            );
            $data = ['id' => $id, 'name' => 'found-rows', 'value' => 'updated'];
            $this->assertNull(Helper::updateOrInsert($foundRowsConnection, self::TABLE_AUTO_INCREMENT, $data, ['id']));
            $this->assertNull(Helper::updateOrInsert($foundRowsConnection, self::TABLE_AUTO_INCREMENT, $data, ['id']));
        } finally {
            $foundRowsConnection->close();
        }

        $row = $this->fetchRowByName('found-rows');
        $this->assertSame($id, (int) $row['id']);
        $this->assertSame('updated', $row['value']);
        $this->assertSame(1, $this->countRows(self::TABLE_AUTO_INCREMENT));
    }

    public function testOnAMysqliConnection(): void
    {
        if (!extension_loaded('mysqli')) {
            $this->markTestSkipped('The mysqli extension is not available.');
        }

        // the match detection reads LAST_INSERT_ID() back through the driver - both drivers see it.
        // The driver options of the test connection are PDO's (MYSQL_ATTR_INIT_COMMAND), which
        // mysqli would reject as unknown options
        $params = $this->db->getParams();
        unset($params['driverClass'], $params['driverOptions']);
        $params['driver'] = 'mysqli';
        $mysqliConnection = \Doctrine\DBAL\DriverManager::getConnection($params);

        try {
            $id = (int) Helper::updateOrInsert(
                $mysqliConnection,
                self::TABLE_AUTO_INCREMENT,
                ['id' => null, 'name' => 'mysqli', 'value' => 'inserted'],
                ['id']
            );
            $data = ['id' => $id, 'name' => 'mysqli', 'value' => 'updated'];
            $this->assertNull(Helper::updateOrInsert($mysqliConnection, self::TABLE_AUTO_INCREMENT, $data, ['id']));
            $this->assertNull(Helper::updateOrInsert($mysqliConnection, self::TABLE_AUTO_INCREMENT, $data, ['id']));
        } finally {
            $mysqliConnection->close();
        }

        $row = $this->fetchRowByName('mysqli');
        $this->assertSame($id, (int) $row['id']);
        $this->assertSame('updated', $row['value']);
        $this->assertSame(1, $this->countRows(self::TABLE_AUTO_INCREMENT));
    }

    public function testLegacyUpsertStillInsertsAndUpdates(): void
    {
        // upsert() keeps its two-statement implementation and contract unchanged
        $lastInsertId = Helper::upsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'legacy', 'value' => 'inserted'],
            ['id']
        );
        $this->assertNotNull($lastInsertId);
        $id = (int) $lastInsertId;

        $updateResult = Helper::upsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => $id, 'name' => 'legacy', 'value' => 'updated'],
            ['id']
        );
        $this->assertNull($updateResult);

        $row = $this->fetchRowByName('legacy');
        $this->assertSame($id, (int) $row['id']);
        $this->assertSame('updated', $row['value']);
        $this->assertSame(1, $this->countRows(self::TABLE_AUTO_INCREMENT));
    }

    private function createTriggerLog(): void
    {
        $this->db->executeStatement(
            'CREATE TABLE ' . self::TABLE_TRIGGER_LOG . ' (
                `event` varchar(20) NOT NULL,
                `id` int(11) NOT NULL
            ) DEFAULT CHARSET=utf8mb4'
        );
    }

    private function fetchRowByName(string $name): array
    {
        $row = $this->db->fetchAssociative(
            'SELECT * FROM ' . self::TABLE_AUTO_INCREMENT . ' WHERE name = ?',
            [$name]
        );

        $this->assertIsArray($row, sprintf('Row "%s" is expected to exist.', $name));

        return $row;
    }

    private function countRows(string $table): int
    {
        return (int) $this->db->fetchOne('SELECT COUNT(*) FROM ' . $table);
    }

    private function dropTables(): void
    {
        // the triggers of a test table are dropped together with it
        $this->db->executeStatement('DROP TABLE IF EXISTS ' . self::TABLE_AUTO_INCREMENT);
        $this->db->executeStatement('DROP TABLE IF EXISTS ' . self::TABLE_COMPOSITE_KEY);
        $this->db->executeStatement('DROP TABLE IF EXISTS ' . self::TABLE_TRIGGER_LOG);
        $this->db->executeStatement('DROP TABLE IF EXISTS ' . self::TABLE_LAST_INSERT_ID_LOG);
    }
}

/**
 * Inserts a row through another connection right after its own UPDATE changed nothing, to
 * reproduce a concurrent insert between updateOrInsert()'s UPDATE and INSERT.
 *
 * @internal
 */
final class ConcurrentInsertConnection extends Connection
{
    public ?Connection $other = null;

    /** @var array<string, mixed> */
    public array $concurrentRow = [];

    public function executeStatement(string $sql, array $params = [], array $types = []): int|string
    {
        $affected = parent::executeStatement($sql, $params, $types);
        if (str_starts_with($sql, 'UPDATE ') && (int) $affected === 0 && $this->other !== null && $this->concurrentRow !== []) {
            $table = explode(' ', $sql, 3)[1];
            $this->other->insert($table, $this->concurrentRow);
            $this->concurrentRow = [];
        }

        return $affected;
    }
}
