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
    /** @var array<string, array{classId: string, isLocalized: bool}> mutating cleanupTable() calls */
    private array $cleaned = [];

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
        $this->cleaned = [];
        $cleaned = &$this->cleaned;

        // Real matcher so the ownership decision is exercised end to end.
        $matcher = new DataObjectTaskHelper(
            $this->createMock(LoggerInterface::class),
            $this->createMock(Connection::class)
        );

        $helper = $this->createMock(DataObjectTaskHelperInterface::class);
        $helper->method('getFieldcollectionCollectionNames')->willReturn($collectionNames);
        $helper->method('matchCollectionKeys')->willReturnCallback(
            static fn (string $id, array $names): array => $matcher->matchCollectionKeys($id, $names)
        );
        // Non-mutating ownership probe: a parse is live when its class id exists.
        $helper->method('classExists')->willReturnCallback(
            static fn (string $classId): bool => in_array($classId, $existingClassIds, true)
        );
        // The mutating row cleanup - recorded so tests can assert it only runs for the single
        // unambiguous live parse (and with the right localized flag).
        $helper->method('cleanupTable')->willReturnCallback(
            static function (string $table, string $classId, bool $isLocalized = true) use (&$cleaned): bool {
                $cleaned[$table] = ['classId' => $classId, 'isLocalized' => $isLocalized];

                return true;
            }
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

    /**
     * Regression: when "Foo" and "Foo_Bar" are both live, "object_collection_Foo_Bar_5" is
     * ambiguous - it may belong to "Foo_Bar" on class "5" or to "Foo" on class "Bar_5". The table
     * is owned as soon as ANY parse resolves to a live class; relying only on the longest key would
     * drop the live table of "Foo" on class "Bar_5" whenever class "5" is missing.
     */
    public function testAmbiguousTableIsKeptWhenAnyParseResolvesToLiveClass(): void
    {
        $collectionNames = ['foo' => 'Foo', 'foo_bar' => 'Foo_Bar'];
        $tables = ['object_collection_Foo_Bar_5'];

        // Class "5" does not exist, but "Bar_5" does: the table belongs to "Foo" -> kept.
        $this->assertSame([], $this->runTask($collectionNames, $tables, ['Bar_5']));

        // Class "5" exists: the table belongs to "Foo_Bar" -> kept.
        $this->assertSame([], $this->runTask($collectionNames, $tables, ['5']));

        // No parse resolves to a live class: only then is the table an orphan -> dropped.
        $this->assertSame(['object_collection_Foo_Bar_5'], $this->runTask($collectionNames, $tables, []));
    }

    /**
     * When SEVERAL parses resolve to live classes ("Foo_Bar" on class "5" and "Foo" on class
     * "Bar_5" both exist), the true owner cannot be determined from the name alone. The table must
     * be kept, and the mutating row cleanup must NOT run - cleaning against the wrong owner's
     * field definitions would delete live rows.
     */
    public function testFullyAmbiguousTableIsKeptWithoutMutation(): void
    {
        $dropped = $this->runTask(
            ['foo' => 'Foo', 'foo_bar' => 'Foo_Bar'],
            ['object_collection_Foo_Bar_5'],
            ['5', 'Bar_5']
        );

        $this->assertSame([], $dropped);
        $this->assertSame([], $this->cleaned);
    }

    /**
     * Class ids may contain underscores, so "localized_5" is a legal class id and
     * "object_collection_Foo_localized_5" has two competing readings: the localized table of "Foo"
     * on class "5", or the plain table of "Foo" on class "localized_5". Whichever class actually
     * exists decides; when both exist, the table is kept without any mutation.
     */
    public function testLocalizedMarkerVersusUnderscoreClassId(): void
    {
        $names = ['foo' => 'Foo'];
        $tables = ['object_collection_Foo_localized_5'];

        // Only class "5" exists -> it is the localized table of "Foo" on class "5".
        $this->assertSame([], $this->runTask($names, $tables, ['5']));
        $this->assertSame(
            ['object_collection_Foo_localized_5' => ['classId' => '5', 'isLocalized' => true]],
            $this->cleaned
        );

        // Only class "localized_5" exists -> it is the plain table of "Foo" on class "localized_5".
        $this->assertSame([], $this->runTask($names, $tables, ['localized_5']));
        $this->assertSame(
            ['object_collection_Foo_localized_5' => ['classId' => 'localized_5', 'isLocalized' => false]],
            $this->cleaned
        );

        // Both exist -> ambiguous: kept, no mutation.
        $this->assertSame([], $this->runTask($names, $tables, ['5', 'localized_5']));
        $this->assertSame([], $this->cleaned);

        // Neither exists -> orphan, dropped.
        $this->assertSame($tables, $this->runTask($names, $tables, []));
    }

    /**
     * Regression for pimcore/platform-version#141: a fieldcollection with an all-lowercase key
     * ("video") stores its definition as fieldcollections/video.php while its generated data class
     * is ucfirst()-ed. The old cleanup built its name map from the generated directory and resolved
     * the ucfirst-ed key via the case-sensitive getByKey(), so on case-sensitive filesystems it
     * logged a spurious "not found" on every run and skipped the row cleanup. The ownership map now
     * comes from the definition files themselves and is matched case-insensitively: the live table
     * is owned and cleaned - and, crucially, never mistaken for an orphan by the new drop logic.
     */
    public function testLowercaseKeyTableIsOwnedAndCleaned(): void
    {
        $dropped = $this->runTask(
            ['video' => 'video'],
            ['object_collection_video_AB12'],
            ['AB12']
        );

        $this->assertSame([], $dropped);
        $this->assertSame(
            ['object_collection_video_AB12' => ['classId' => 'AB12', 'isLocalized' => false]],
            $this->cleaned
        );
    }
}
