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
 * Tests for Db\Helper::upsertByUniqueKey() and the legacy Db\Helper::upsert() it falls back to.
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

    private const TABLE_NULLABLE_KEY = 'test_upsert_nullable_key';

    private const TABLE_TRIGGER_LOG = 'test_upsert_trigger_log';

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

        // a nullable unique key column next to a second unique index - not something a core
        // table has, but the one shape where a null key value can meet a stored NULL
        $this->db->executeStatement(
            'CREATE TABLE ' . self::TABLE_NULLABLE_KEY . ' (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `code` varchar(50) DEFAULT NULL,
                `name` varchar(50) NOT NULL,
                `value` varchar(50) DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `code` (`code`),
                UNIQUE KEY `name` (`name`)
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
        $lastInsertId = Helper::upsertByUniqueKey(
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

    public function testUpdateReturnsNullAndUpdatesTheRow(): void
    {
        $id = (int) Helper::upsertByUniqueKey(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'first', 'value' => 'inserted'],
            ['id']
        );

        $lastInsertId = Helper::upsertByUniqueKey(
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
        Helper::upsertByUniqueKey($this->db, self::TABLE_AUTO_INCREMENT, $data, ['id']);

        // MySQL reports 0 affected rows for a duplicate key update that changes nothing
        $lastInsertId = Helper::upsertByUniqueKey($this->db, self::TABLE_AUTO_INCREMENT, $data, ['id']);

        $this->assertNull($lastInsertId, 'An update that changes nothing must not return an id either.');
        $this->assertSame(1, $this->countRows(self::TABLE_AUTO_INCREMENT));
    }

    public function testInsertAfterAnUpdateReturnsTheNewId(): void
    {
        $firstId = (int) Helper::upsertByUniqueKey(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'first', 'value' => 'inserted'],
            ['id']
        );

        Helper::upsertByUniqueKey(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => $firstId, 'name' => 'first', 'value' => 'updated'],
            ['id']
        );

        $secondId = Helper::upsertByUniqueKey(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'second', 'value' => 'inserted'],
            ['id']
        );

        $this->assertNotNull($secondId);
        $this->assertNotSame($firstId, (int) $secondId, 'An insert following an update must return the new id.');
        $this->assertSame((int) $secondId, (int) $this->fetchRowByName('second')['id']);
    }

    public function testUpsertOnATableWithoutAutoIncrement(): void
    {
        $data = ['cid' => 5, 'ctype' => 'object', 'key' => 'inserted'];
        $keys = ['cid', 'ctype'];

        $insertResult = Helper::upsertByUniqueKey($this->db, self::TABLE_COMPOSITE_KEY, $data, $keys);
        // there is no auto increment column, so there is no id to report
        $this->assertSame(0, (int) $insertResult);

        $data['key'] = 'updated';
        $updateResult = Helper::upsertByUniqueKey($this->db, self::TABLE_COMPOSITE_KEY, $data, $keys);

        $this->assertNull($updateResult, 'The update path must not return an id.');
        $this->assertSame(1, $this->countRows(self::TABLE_COMPOSITE_KEY));
        $this->assertSame(
            'updated',
            $this->db->fetchOne('SELECT `key` FROM ' . self::TABLE_COMPOSITE_KEY . ' WHERE cid = 5')
        );
    }

    public function testUpsertWithoutQuotedIdentifiers(): void
    {
        $data = ['cid' => 7, 'ctype' => 'asset'];
        $keys = ['cid', 'ctype'];

        Helper::upsertByUniqueKey($this->db, self::TABLE_COMPOSITE_KEY, $data, $keys, false);
        $updateResult = Helper::upsertByUniqueKey($this->db, self::TABLE_COMPOSITE_KEY, $data, $keys, false);

        $this->assertNull($updateResult);
        $this->assertSame(1, $this->countRows(self::TABLE_COMPOSITE_KEY));
    }

    public function testMissingKeyThrowsWithoutWriting(): void
    {
        $data = ['cid' => 9, 'ctype' => 'document', 'key' => 'inserted'];
        Helper::upsertByUniqueKey($this->db, self::TABLE_COMPOSITE_KEY, $data, ['cid', 'ctype']);

        $caught = null;

        try {
            $data['key'] = 'changed';
            Helper::upsertByUniqueKey($this->db, self::TABLE_COMPOSITE_KEY, $data, ['cid', 'missing']);
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
        // BC pin: the previous implementation read $keys only after a duplicate, so an insert
        // that does not collide succeeds even when a listed key is absent from $data (the
        // return value is unspecified for tables without an identity column, as before)
        Helper::upsertByUniqueKey(
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
        // BC pin: AbstractDao::getPrimaryKey() returns [] for a table without a primary key, and
        // the previous implementation inserted fine with it - only a duplicate misbehaved
        Helper::upsertByUniqueKey(
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

    public function testFoundRowsConnectionFallsBackToTheLegacyPath(): void
    {
        // an install enabling CLIENT_FOUND_ROWS in the doctrine driverOptions worked before and
        // must keep working - the single statement cannot tell insert from update there, so the
        // previous two-statement path is used, which does not depend on the option
        $params = $this->db->getParams();
        $params['driverOptions'][PDO::MYSQL_ATTR_FOUND_ROWS] = true;
        $foundRowsConnection = \Doctrine\DBAL\DriverManager::getConnection($params);

        try {
            $lastInsertId = Helper::upsertByUniqueKey(
                $foundRowsConnection,
                self::TABLE_AUTO_INCREMENT,
                ['id' => null, 'name' => 'found-rows', 'value' => 'inserted'],
                ['id']
            );
            $this->assertNotNull($lastInsertId, 'The insert path has to return the generated id.');
            $id = (int) $lastInsertId;

            $updateResult = Helper::upsertByUniqueKey(
                $foundRowsConnection,
                self::TABLE_AUTO_INCREMENT,
                ['id' => $id, 'name' => 'found-rows', 'value' => 'updated'],
                ['id']
            );
            $this->assertNull($updateResult, 'The update path must not return an id.');

            // the case a FOUND_ROWS connection would misreport as an insert on the single statement
            $unchangedResult = Helper::upsertByUniqueKey(
                $foundRowsConnection,
                self::TABLE_AUTO_INCREMENT,
                ['id' => $id, 'name' => 'found-rows', 'value' => 'updated'],
                ['id']
            );
            $this->assertNull($unchangedResult, 'An update that changes nothing must not return an id either.');
        } finally {
            $foundRowsConnection->close();
        }

        $row = $this->fetchRowByName('found-rows');
        $this->assertSame($id, (int) $row['id']);
        $this->assertSame('updated', $row['value']);
        $this->assertSame(1, $this->countRows(self::TABLE_AUTO_INCREMENT));
    }

    public function testConflictOnNonKeyUniqueIndexLeavesTheForeignRowUntouched(): void
    {
        $id = (int) Helper::upsertByUniqueKey(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'first', 'value' => 'inserted'],
            ['id']
        );

        // a different id colliding with the existing row's unique `name`: ON DUPLICATE KEY fires
        // for that row, but $keys select no row - so nothing may be written, matching the
        // previous implementation's UPDATE ... WHERE id = <other id> matching zero rows
        $result = Helper::upsertByUniqueKey(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => $id + 1000, 'name' => 'first', 'value' => 'hijacked'],
            ['id']
        );

        $this->assertNull($result, 'A conflict on a non-key unique index is the update path and must not return an id.');
        $this->assertSame(1, $this->countRows(self::TABLE_AUTO_INCREMENT));

        $row = $this->fetchRowByName('first');
        $this->assertSame($id, (int) $row['id'], 'The foreign row must keep its id.');
        $this->assertSame('inserted', $row['value'], 'The foreign row must keep its values.');
    }

    public function testConflictOnNonKeyUniqueIndexWithAnExistingKeyedRowThrowsWithoutWriting(): void
    {
        $firstId = (int) Helper::upsertByUniqueKey(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'first', 'value' => 'inserted'],
            ['id']
        );
        $secondId = (int) Helper::upsertByUniqueKey(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'second', 'value' => 'inserted'],
            ['id']
        );

        try {
            // the keyed row exists AND the new values collide with another row's unique `name`:
            // the guard selects the keyed row, and writing `name` to it violates the unique index -
            // the same UniqueConstraintViolationException the previous implementation's
            // UPDATE ... WHERE id = <second id> raised
            Helper::upsertByUniqueKey(
                $this->db,
                self::TABLE_AUTO_INCREMENT,
                ['id' => $secondId, 'name' => 'first', 'value' => 'hijacked'],
                ['id']
            );
            $this->fail('Expected UniqueConstraintViolationException was not thrown.');
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
        }

        $this->assertSame(2, $this->countRows(self::TABLE_AUTO_INCREMENT));

        $first = $this->fetchRowByName('first');
        $this->assertSame($firstId, (int) $first['id'], 'The row owning the unique value must keep its id.');
        $this->assertSame('inserted', $first['value'], 'The row owning the unique value must keep its values.');

        $second = $this->fetchRowByName('second');
        $this->assertSame($secondId, (int) $second['id'], 'The keyed row must keep its id.');
        $this->assertSame('inserted', $second['value'], 'The keyed row must not be partially written.');
    }

    public function testKeyColumnsAreWrittenOnTheUpdatePath(): void
    {
        // the previous UPDATE wrote all of $data including the key columns, so a key value that
        // compares equal but is stored differently - here under the case-insensitive collation
        // of the test table - was updated to the incoming representation
        Helper::upsertByUniqueKey(
            $this->db,
            self::TABLE_COMPOSITE_KEY,
            ['cid' => 15, 'ctype' => 'object', 'key' => 'inserted'],
            ['cid', 'ctype']
        );

        $result = Helper::upsertByUniqueKey(
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

    public function testNonUniqueKeysUpdateOnlyTheConflictingRow(): void
    {
        // contract pin: the key columns have to be the primary key or a unique index. With
        // non-unique criteria matching several rows, upsert()'s UPDATE ... WHERE writes the
        // conflicting unique value to all of them and fails with a unique constraint violation;
        // ON DUPLICATE KEY UPDATE can only touch the row the conflict was detected on
        $firstId = (int) Helper::upsertByUniqueKey(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'first', 'value' => 'shared'],
            ['id']
        );
        $secondId = (int) Helper::upsertByUniqueKey(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'second', 'value' => 'shared'],
            ['id']
        );

        $result = Helper::upsertByUniqueKey(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => $firstId, 'name' => 'first-renamed', 'value' => 'shared'],
            ['value']
        );

        $this->assertNull($result);
        $this->assertSame(2, $this->countRows(self::TABLE_AUTO_INCREMENT));
        $this->assertSame(
            'first-renamed',
            $this->db->fetchOne('SELECT `name` FROM ' . self::TABLE_AUTO_INCREMENT . ' WHERE id = ?', [$firstId]),
            'The conflicting row matching the criteria is updated.'
        );
        $this->assertSame(
            'second',
            $this->db->fetchOne('SELECT `name` FROM ' . self::TABLE_AUTO_INCREMENT . ' WHERE id = ?', [$secondId]),
            'Another row matching the non-unique criteria is not touched.'
        );
    }

    public function testConflictOnNonKeyUniqueIndexRunsTheForeignRowsBeforeUpdateTrigger(): void
    {
        // contract pin: ON DUPLICATE KEY UPDATE runs the conflicting row's BEFORE UPDATE
        // triggers even though every guarded assignment keeps the stored value (AFTER UPDATE
        // triggers do not run for an unchanged row). upsert()'s UPDATE ... WHERE matches no row
        // in this situation and runs no trigger.
        $this->db->executeStatement(
            'CREATE TABLE ' . self::TABLE_TRIGGER_LOG . ' (
                `event` varchar(20) NOT NULL,
                `id` int(11) NOT NULL,
                `old_value` varchar(50) DEFAULT NULL,
                `new_value` varchar(50) DEFAULT NULL
            ) DEFAULT CHARSET=utf8mb4'
        );
        // the triggers are dropped together with their table in tearDown()
        $this->db->executeStatement(
            'CREATE TRIGGER test_upsert_before_update BEFORE UPDATE ON ' . self::TABLE_AUTO_INCREMENT
            . ' FOR EACH ROW INSERT INTO ' . self::TABLE_TRIGGER_LOG . " VALUES ('before_update', OLD.id, OLD.value, NEW.value)"
        );
        $this->db->executeStatement(
            'CREATE TRIGGER test_upsert_after_update AFTER UPDATE ON ' . self::TABLE_AUTO_INCREMENT
            . ' FOR EACH ROW INSERT INTO ' . self::TABLE_TRIGGER_LOG . " VALUES ('after_update', OLD.id, OLD.value, NEW.value)"
        );

        $id = (int) Helper::upsertByUniqueKey(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'first', 'value' => 'inserted'],
            ['id']
        );
        $this->db->executeStatement('DELETE FROM ' . self::TABLE_TRIGGER_LOG);

        $result = Helper::upsertByUniqueKey(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => $id + 1000, 'name' => 'first', 'value' => 'hijacked'],
            ['id']
        );

        $this->assertNull($result);
        $this->assertSame(
            [['event' => 'before_update', 'id' => (string) $id, 'old_value' => 'inserted', 'new_value' => 'inserted']],
            array_map(
                static fn (array $row): array => ['event' => $row['event'], 'id' => (string) $row['id'], 'old_value' => $row['old_value'], 'new_value' => $row['new_value']],
                $this->db->fetchAllAssociative('SELECT `event`, `id`, `old_value`, `new_value` FROM ' . self::TABLE_TRIGGER_LOG)
            ),
            'Only the BEFORE UPDATE trigger runs, and it sees the unchanged values.'
        );

        $row = $this->fetchRowByName('first');
        $this->assertSame($id, (int) $row['id']);
        $this->assertSame('inserted', $row['value'], 'The foreign row must keep its values.');
        $this->assertSame(1, $this->countRows(self::TABLE_AUTO_INCREMENT));
    }

    public function testNullKeyDoesNotMatchAStoredNullKey(): void
    {
        $this->db->executeStatement(
            'INSERT INTO ' . self::TABLE_NULLABLE_KEY . " (`code`, `name`, `value`) VALUES (NULL, 'first', 'inserted')"
        );

        try {
            // the stored `code` is NULL as well: a NULL-safe comparison would match it, write the
            // row and only then report the misuse - the guard must not match, so nothing is written
            Helper::upsertByUniqueKey(
                $this->db,
                self::TABLE_NULLABLE_KEY,
                ['code' => null, 'name' => 'first', 'value' => 'hijacked'],
                ['code']
            );
            $this->fail('Expected LogicException was not thrown.');
        } catch (LogicException $e) {
            $this->assertSame('Key "`code`" passed for upsert not found in data', $e->getMessage());
        }

        $this->assertSame(1, $this->countRows(self::TABLE_NULLABLE_KEY));
        $this->assertSame(
            'inserted',
            $this->db->fetchOne('SELECT `value` FROM ' . self::TABLE_NULLABLE_KEY . " WHERE name = 'first'"),
            'The row storing NULL in the key column must not be modified.'
        );

        // while a null key value that does not collide still inserts normally
        $lastInsertId = Helper::upsertByUniqueKey(
            $this->db,
            self::TABLE_NULLABLE_KEY,
            ['code' => null, 'name' => 'second', 'value' => 'inserted'],
            ['code']
        );
        $this->assertNotNull($lastInsertId);
        $this->assertSame(2, $this->countRows(self::TABLE_NULLABLE_KEY));
    }

    public function testNullKeyWithNonKeyUniqueConflictThrowsWithoutWriting(): void
    {
        $id = (int) Helper::upsertByUniqueKey(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'first', 'value' => 'inserted'],
            ['id']
        );

        try {
            // null id + unique `name` conflict: previously LogicException while building the
            // WHERE clause; now the guard skips the foreign row and the same misuse is reported
            // after the statement - in both cases without writing anything
            Helper::upsertByUniqueKey(
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
        $this->db->executeStatement('DROP TABLE IF EXISTS ' . self::TABLE_AUTO_INCREMENT);
        $this->db->executeStatement('DROP TABLE IF EXISTS ' . self::TABLE_COMPOSITE_KEY);
        $this->db->executeStatement('DROP TABLE IF EXISTS ' . self::TABLE_NULLABLE_KEY);
        $this->db->executeStatement('DROP TABLE IF EXISTS ' . self::TABLE_TRIGGER_LOG);
    }
}
