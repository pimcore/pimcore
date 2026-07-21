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
     *
     * @return string[] the tables the task decided to drop
     */
    private function runTask(array $collectionNames, array $tablesByType): array
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
        $helper->method('matchCollectionKey')->willReturnCallback(
            static fn (string $id, array $names): ?string => $matcher->matchCollectionKey($id, $names)
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
}
