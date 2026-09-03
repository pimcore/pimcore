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
use Pimcore\Db;
use Pimcore\Db\Helper;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * Tests for Db\Helper::upsert().
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
        $lastInsertId = Helper::upsert(
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
        $id = (int) Helper::upsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'first', 'value' => 'inserted'],
            ['id']
        );

        $lastInsertId = Helper::upsert(
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
        Helper::upsert($this->db, self::TABLE_AUTO_INCREMENT, $data, ['id']);

        // MySQL reports 0 affected rows for a duplicate key update that changes nothing
        $lastInsertId = Helper::upsert($this->db, self::TABLE_AUTO_INCREMENT, $data, ['id']);

        $this->assertNull($lastInsertId, 'An update that changes nothing must not return an id either.');
        $this->assertSame(1, $this->countRows(self::TABLE_AUTO_INCREMENT));
    }

    public function testInsertAfterAnUpdateReturnsTheNewId(): void
    {
        $firstId = (int) Helper::upsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => null, 'name' => 'first', 'value' => 'inserted'],
            ['id']
        );

        Helper::upsert(
            $this->db,
            self::TABLE_AUTO_INCREMENT,
            ['id' => $firstId, 'name' => 'first', 'value' => 'updated'],
            ['id']
        );

        $secondId = Helper::upsert(
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

        $insertResult = Helper::upsert($this->db, self::TABLE_COMPOSITE_KEY, $data, $keys);
        // there is no auto increment column, so there is no id to report
        $this->assertSame(0, (int) $insertResult);

        $data['key'] = 'updated';
        $updateResult = Helper::upsert($this->db, self::TABLE_COMPOSITE_KEY, $data, $keys);

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

        Helper::upsert($this->db, self::TABLE_COMPOSITE_KEY, $data, $keys, false);
        $updateResult = Helper::upsert($this->db, self::TABLE_COMPOSITE_KEY, $data, $keys, false);

        $this->assertNull($updateResult);
        $this->assertSame(1, $this->countRows(self::TABLE_COMPOSITE_KEY));
    }

    public function testMissingKeyThrowsOnTheUpdatePath(): void
    {
        $data = ['cid' => 9, 'ctype' => 'document', 'key' => 'inserted'];
        Helper::upsert($this->db, self::TABLE_COMPOSITE_KEY, $data, ['cid', 'ctype']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Key "`missing`" passed for upsert not found in data');

        Helper::upsert($this->db, self::TABLE_COMPOSITE_KEY, $data, ['cid', 'missing']);
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
    }
}
