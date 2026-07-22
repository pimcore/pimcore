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
use Pimcore\Maintenance\Tasks\DataObject\CleanupFieldcollectionTablesTaskHelper;
use Pimcore\Maintenance\Tasks\DataObject\DataObjectTaskHelper;
use Pimcore\Maintenance\Tasks\DataObject\DataObjectTaskHelperInterface;
use Psr\Log\LoggerInterface;

/**
 * Exercises the destructive branch of the fieldcollection maintenance cleanup: an orphaned table
 * must be dropped, while every live table - including keys with underscores, localized tables and
 * definitions loaded only from the custom configuration directory - must be kept.
 */
class CleanupFieldcollectionTablesTaskHelperTest extends TestCase
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
     * @param array<string, string> $collectionNames lowercased => actual fieldcollection key
     * @param string[] $tables
     * @param string[] $existingClassIds class ids that resolve to a live class definition
     *
     * @return string[] the tables the task decided to drop
     */
    private function runTask(array $collectionNames, array $tables, array $existingClassIds = ['5']): array
    {
        $dropped = [];

        // Real matcher so the ownership decision is exercised end to end.
        $matcher = new DataObjectTaskHelper(
            $this->createMock(LoggerInterface::class),
            $this->createMock(Connection::class)
        );

        $helper = $this->createMock(DataObjectTaskHelperInterface::class);
        $helper->method('getFieldcollectionCollectionNames')->willReturn($collectionNames);
        $helper->method('matchCollectionKey')->willReturnCallback(
            static fn (string $id, array $names): ?string => $matcher->matchCollectionKey($id, $names)
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
        $db->method('fetchAllAssociative')->willReturn(
            array_map(static fn (string $t): array => ['tableName' => $t], $tables)
        );

        (new CleanupFieldcollectionTablesTaskHelper($helper, $db))->cleanupCollectionTable();

        sort($dropped);

        return $dropped;
    }

    public function testDropsOnlyOrphanFieldcollectionTables(): void
    {
        $collectionNames = [
            'foo' => 'Foo',
            'foo_bar' => 'Foo_Bar',
            // Simulates a fieldcollection defined only in the custom configuration directory.
            'custom' => 'Custom',
        ];

        $dropped = $this->runTask($collectionNames, [
            'object_collection_Foo_5',             // live
            'object_collection_Foo_Bar_5',         // live, underscore key (regression guard)
            'object_collection_Foo_localized_5',   // live, localized
            'object_collection_Custom_5',          // live, custom-config only
            'object_collection_Ghost_5',           // orphan
            'object_collection_Ghost_localized_5', // orphan, localized
        ]);

        $this->assertSame([
            'object_collection_Ghost_5',
            'object_collection_Ghost_localized_5',
        ], $dropped);
    }

    public function testRemovingLastDefinitionDropsItsOrphanTables(): void
    {
        $dropped = $this->runTask([], ['object_collection_Ghost_5']);

        $this->assertSame(['object_collection_Ghost_5'], $dropped);
    }

    /**
     * Regression: a removed underscore-containing key ("Foo_Bar") whose prefix ("Foo") still has a
     * live definition. The matcher resolves "object_collection_Foo_Bar_5" to key "Foo" and a bogus
     * class id "Bar_5"; because that class id does not resolve to a live class, the table must be
     * dropped as an orphan. The genuinely live "object_collection_Foo_5" (class id "5") is kept.
     */
    public function testRemovedUnderscoreKeyPrefixedByLiveKeyIsDropped(): void
    {
        // "Foo_Bar" was removed; only "Foo" survives. Class id "5" exists, "Bar_5" does not.
        $dropped = $this->runTask(
            ['foo' => 'Foo'],
            [
                'object_collection_Foo_5',     // live -> kept
                'object_collection_Foo_Bar_5', // orphan of removed "Foo_Bar" -> dropped
            ],
            ['5']
        );

        $this->assertSame(['object_collection_Foo_Bar_5'], $dropped);
    }
}
