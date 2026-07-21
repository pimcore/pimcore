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
     *
     * @return string[] the tables the task decided to drop
     */
    private function runTask(array $collectionNames, array $tables): array
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
}
