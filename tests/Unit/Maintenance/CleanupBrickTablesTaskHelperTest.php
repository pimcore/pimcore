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

namespace Pimcore\Tests\Unit\Maintenance;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Pimcore\Maintenance\Tasks\DataObject\CleanupBrickTablesTaskHelper;
use Pimcore\Maintenance\Tasks\DataObject\DataObjectTaskHelper;
use Pimcore\Maintenance\Tasks\DataObject\DataObjectTaskHelperInterface;
use Psr\Log\LoggerInterface;

/**
 * Exercises the destructive branch of the object-brick maintenance cleanup: an orphaned table (no
 * existing definition owns it) must be dropped, while every live table - including keys with
 * underscores and definitions loaded only from the custom configuration directory - must be kept.
 */
class CleanupBrickTablesTaskHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The task bails out when the class definition store is unavailable; ensure it exists so
        // the drop logic under test is actually reached.
        if (!is_dir(PIMCORE_CLASS_DEFINITION_DIRECTORY)) {
            mkdir(PIMCORE_CLASS_DEFINITION_DIRECTORY, 0775, true);
        }
    }

    /**
     * @param array<string, string> $collectionNames lowercased => actual brick key
     * @param array<string, string[]> $tablesByType   table type (store|query|localized) => table names
     * @param string[] $existingClassIds              class ids that resolve to a live class definition
     *
     * @return string[] the tables the task decided to drop
     */
    private function runTask(array $collectionNames, array $tablesByType, array $existingClassIds = ['5']): array
    {
        $dropped = [];

        // Real matcher so the ownership decision is exercised end to end (it uses neither the
        // logger nor the connection).
        $matcher = new DataObjectTaskHelper(
            $this->createMock(LoggerInterface::class),
            $this->createMock(Connection::class)
        );

        $helper = $this->createMock(DataObjectTaskHelperInterface::class);
        $helper->method('getObjectBrickCollectionNames')->willReturn($collectionNames);
        $helper->method('matchCollectionKeys')->willReturnCallback(
            static fn (string $id, array $names): array => $matcher->matchCollectionKeys($id, $names)
        );
        // Models the real cleanupTable(): it keeps the table (returns true) only when the parsed
        // class id resolves to a live class definition, and reports it as unowned otherwise.
        $helper->method('cleanupTable')->willReturnCallback(
            static fn (string $table, string $classId): bool => in_array($classId, $existingClassIds, true)
        );
        $helper->method('dropOrphanedTable')->willReturnCallback(
            static function (string $table) use (&$dropped): void {
                $dropped[] = $table;
            }
        );

        $db = $this->createMock(Connection::class);
        $db->method('fetchAllAssociative')->willReturnCallback(
            static function (string $sql) use ($tablesByType): array {
                foreach ($tablesByType as $type => $tables) {
                    if (str_contains($sql, 'object\_brick\_' . $type . '\_')) {
                        return array_map(static fn (string $t): array => ['tableName' => $t], $tables);
                    }
                }

                return [];
            }
        );

        (new CleanupBrickTablesTaskHelper($helper, $db))->cleanupCollectionTable();

        sort($dropped);

        return $dropped;
    }

    public function testDropsOnlyOrphanTablesAcrossAllTypes(): void
    {
        $collectionNames = [
            'foo' => 'Foo',
            'foo_bar' => 'Foo_Bar',
            // Simulates a brick defined only in the custom configuration directory: the ownership
            // source includes it, so its live tables must be kept.
            'custombrick' => 'CustomBrick',
        ];

        $dropped = $this->runTask($collectionNames, [
            'store' => [
                'object_brick_store_Foo_5',         // live
                'object_brick_store_Foo_Bar_5',     // live, underscore key (regression guard)
                'object_brick_store_CustomBrick_5', // live, custom-config only
                'object_brick_store_Ghost_5',       // orphan
            ],
            'query' => [
                'object_brick_query_Ghost_5',       // orphan
            ],
            'localized' => [
                'object_brick_localized_Ghost_5',          // orphan
                'object_brick_localized_query_Ghost_5_en', // orphan localized-query
                'object_brick_localized_query_Foo_5_en',   // live localized-query -> kept
            ],
        ]);

        $this->assertSame([
            'object_brick_localized_Ghost_5',
            'object_brick_localized_query_Ghost_5_en',
            'object_brick_query_Ghost_5',
            'object_brick_store_Ghost_5',
        ], $dropped);
    }

    public function testRemovingLastDefinitionDropsItsOrphanTables(): void
    {
        // No brick definitions left at all -> every brick table is an orphan.
        $dropped = $this->runTask([], [
            'store' => ['object_brick_store_Ghost_5'],
        ]);

        $this->assertSame(['object_brick_store_Ghost_5'], $dropped);
    }

    /**
     * Regression: a removed underscore-containing key ("Foo_Bar") whose prefix ("Foo") still has a
     * live definition. The matcher resolves "object_brick_store_Foo_Bar_5" to key "Foo" and a bogus
     * class id "Bar_5"; because that class id does not resolve to a live class, the table must be
     * dropped as an orphan instead of leaving it (and spamming the log) forever. The genuinely live
     * "object_brick_store_Foo_5" (class id "5") must be kept.
     */
    public function testRemovedUnderscoreKeyPrefixedByLiveKeyIsDropped(): void
    {
        // "Foo_Bar" was removed; only "Foo" survives. Class id "5" exists, "Bar_5" does not.
        $dropped = $this->runTask(
            ['foo' => 'Foo'],
            [
                'store' => [
                    'object_brick_store_Foo_5',     // live -> kept
                    'object_brick_store_Foo_Bar_5', // orphan of removed "Foo_Bar" -> dropped
                ],
            ],
            ['5']
        );

        $this->assertSame(['object_brick_store_Foo_Bar_5'], $dropped);
    }

    /**
     * Regression: when "Foo" and "Foo_Bar" are both live, "object_brick_store_Foo_Bar_5" is
     * ambiguous - it may belong to brick "Foo_Bar" on class "5" or to brick "Foo" on class "Bar_5".
     * The table is owned as soon as ANY parse resolves to a live class; relying only on the longest
     * key would drop the live table of brick "Foo" on class "Bar_5" whenever class "5" is missing.
     */
    public function testAmbiguousTableIsKeptWhenAnyParseResolvesToLiveClass(): void
    {
        $collectionNames = ['foo' => 'Foo', 'foo_bar' => 'Foo_Bar'];
        $tables = ['store' => ['object_brick_store_Foo_Bar_5']];

        // Class "5" does not exist, but "Bar_5" does: the table belongs to brick "Foo" -> kept.
        $this->assertSame([], $this->runTask($collectionNames, $tables, ['Bar_5']));

        // Class "5" exists: the table belongs to brick "Foo_Bar" -> kept.
        $this->assertSame([], $this->runTask($collectionNames, $tables, ['5']));

        // No parse resolves to a live class: only then is the table an orphan -> dropped.
        $this->assertSame(['object_brick_store_Foo_Bar_5'], $this->runTask($collectionNames, $tables, []));
    }
}
