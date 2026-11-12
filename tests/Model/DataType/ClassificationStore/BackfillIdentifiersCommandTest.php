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
use Pimcore\Bundle\CoreBundle\Command\BackfillClassificationStoreIdentifiersCommand;
use Pimcore\Bundle\CoreBundle\Migrations\Version20261112100000;
use Pimcore\Db;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\IdentifierBackfill;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use Pimcore\Tests\Support\Test\ModelTestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group model.classificationstore.identifier
 */
final class BackfillIdentifiersCommandTest extends ModelTestCase
{
    use ClassificationStoreAutoIncrementTrait;

    private const TABLE_STORES = 'classificationstore_stores';

    private const TABLE_COLLECTIONS = 'classificationstore_collections';

    private const TABLE_GROUPS = 'classificationstore_groups';

    private const TABLE_KEYS = 'classificationstore_keys';

    private const TABLE_SUBGROUPS = 'classificationstore_subgroups';

    private const PRESET_IDENTIFIER = '01JA0PQ7XK';

    private const TABLES = [
        self::TABLE_STORES,
        self::TABLE_COLLECTIONS,
        self::TABLE_GROUPS,
        self::TABLE_SUBGROUPS,
        self::TABLE_KEYS,
    ];

    private const IDENTIFIER_PATTERN = '/\A[A-Za-z0-9][A-Za-z0-9_-]*\z/';

    private array $createdStoreIds = [];

    protected function tearDown(): void
    {
        $db = Db::get();

        foreach ($this->createdStoreIds as $storeId) {
            foreach ([self::TABLE_COLLECTIONS, self::TABLE_GROUPS, self::TABLE_SUBGROUPS, self::TABLE_KEYS] as $table) {
                $db->delete($table, ['storeId' => $storeId]);
            }
            $db->delete(self::TABLE_STORES, ['id' => $storeId]);
        }

        $this->createdStoreIds = [];
        $this->restoreClassificationStoreAutoIncrement();

        parent::tearDown();
    }

    public function testEveryRowIncludingDisabledKeysIsBackfilled(): void
    {
        $storeId = $this->seedStore();
        $enabledKeyId = $this->seedKey($storeId, 'Enabled Key', true);
        $disabledKeyId = $this->seedKey($storeId, 'Disabled Key', false);
        $groupId = $this->seedRow(self::TABLE_GROUPS, $storeId, 'Backfill Group');
        $collectionId = $this->seedRow(self::TABLE_COLLECTIONS, $storeId, 'Backfill Collection');
        $subGroupId = $this->seedRow(self::TABLE_SUBGROUPS, $storeId, 'Backfill Sub Group');

        $this->runCommand();

        $this->assertDerivedIdentifier(self::TABLE_STORES, $storeId);
        $this->assertDerivedIdentifier(self::TABLE_KEYS, $enabledKeyId);
        $this->assertDerivedIdentifier(self::TABLE_KEYS, $disabledKeyId);
        $this->assertDerivedIdentifier(self::TABLE_GROUPS, $groupId);
        $this->assertDerivedIdentifier(self::TABLE_COLLECTIONS, $collectionId);
        $this->assertDerivedIdentifier(self::TABLE_SUBGROUPS, $subGroupId);

        $this->assertSame(
            0,
            (int) Db::get()->fetchOne(
                'SELECT enabled FROM ' . self::TABLE_KEYS . ' WHERE id = ?',
                [$disabledKeyId]
            ),
            'The disabled key must stay disabled'
        );
    }

    public function testEveryNullIdentifierIsBackfilledWithAnOpaqueOneRegardlessOfTheName(): void
    {
        $storeId = $this->seedStore();
        $firstHeightId = $this->seedKey($storeId, 'Height', true);
        $secondHeightId = $this->seedKey($storeId, 'Height', true);
        $namelessId = $this->seedKey($storeId, '...', true);
        $widthId = $this->seedKey($storeId, 'Width', true);
        $firstGroupId = $this->seedRow(self::TABLE_GROUPS, $storeId, 'Shared');
        $secondGroupId = $this->seedRow(self::TABLE_GROUPS, $storeId, 'Shared');

        $this->assertSame(7, $this->countNullIdentifiers($storeId), 'Every seeded row starts without one');

        $tester = $this->runCommand();

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertSame(0, $this->countNullIdentifiers($storeId));

        $this->assertGeneratedIdentifiersDiffer(
            self::TABLE_KEYS,
            [$firstHeightId, $secondHeightId, $namelessId, $widthId],
            ['height', 'width']
        );
        $this->assertGeneratedIdentifiersDiffer(self::TABLE_GROUPS, [$firstGroupId, $secondGroupId], ['shared']);

        $display = $tester->getDisplay();
        foreach ([
            'the name is not unique within its store',
            'the name yields no valid identifier',
            'the derived identifier is already in use',
        ] as $retiredReason) {
            $this->assertStringNotContainsString($retiredReason, $display);
        }
        $this->assertSame([], $this->unresolvedLines($display));
    }

    public function testASubGroupIsBackfilledAndReadByItsConfig(): void
    {
        $storeId = $this->seedStore();
        $subGroupId = $this->seedRow(self::TABLE_SUBGROUPS, $storeId, 'Sub Group');

        $this->assertNull(SubGroupConfig::getById($subGroupId)?->getIdentifier());

        $this->runCommand();

        $this->assertNotNull(SubGroupConfig::getById($subGroupId)?->getIdentifier());
    }

    public function testARowWithAnIdentifierKeepsIt(): void
    {
        $storeId = $this->seedStore();
        $identifiedId = $this->seedKey($storeId, 'Height', true);
        $unidentifiedId = $this->seedKey($storeId, 'Height', true);
        Db::get()->update(self::TABLE_KEYS, ['identifier' => self::PRESET_IDENTIFIER], ['id' => $identifiedId]);

        $this->runCommand();

        $this->assertSame(self::PRESET_IDENTIFIER, $this->fetchIdentifier(self::TABLE_KEYS, $identifiedId));
        $this->assertNotNull($this->fetchIdentifier(self::TABLE_KEYS, $unidentifiedId));
    }

    public function testTheMigrationBackfillsEveryTableAndIsIdempotent(): void
    {
        $storeId = $this->seedStore();
        $this->seedKey($storeId, 'Height', true);
        $this->seedRow(self::TABLE_GROUPS, $storeId, 'Migration Group');
        $this->seedRow(self::TABLE_COLLECTIONS, $storeId, 'Migration Collection');
        $this->seedRow(self::TABLE_SUBGROUPS, $storeId, 'Migration Sub Group');

        $db = Db::get();
        $migration = new Version20261112100000($db, new NullLogger());
        $schema = $db->createSchemaManager()->introspectSchema();

        $migration->up($schema);
        $this->assertSame(0, $this->countNullIdentifiers($storeId));

        $afterFirstUp = $this->snapshot();
        $migration->up($schema);
        $this->assertSame($afterFirstUp, $this->snapshot(), 'A second up() must change nothing');

        $migration->down($schema);
        $this->assertSame([], $migration->getSql(), 'down() must plan no SQL');
        $this->assertSame($afterFirstUp, $this->snapshot(), 'down() must keep every identifier');
    }

    public function testAnIdentifierAssignedAfterThePreflightIsPreservedAndNotCounted(): void
    {
        $storeId = $this->seedStore();
        $keyId = $this->seedKey($storeId, 'Volume', true);

        $backfill = new class($keyId) extends IdentifierBackfill {
            public function __construct(private readonly int $targetId)
            {
            }

            protected function write(Connection $db, string $table, array $assignment): bool
            {
                // stand in for the request that assigns an identifier between the pre-flight and this write
                if ($table === 'classificationstore_keys' && $assignment['id'] === $this->targetId) {
                    $db->update($table, ['identifier' => 'assigned-meanwhile'], ['id' => $this->targetId]);
                }

                return parent::write($db, $table, $assignment);
            }
        };

        $command = new class($backfill) extends BackfillClassificationStoreIdentifiersCommand {
            public function __construct(private readonly IdentifierBackfill $backfill)
            {
                // the AsCommand attribute is not inherited by an anonymous subclass
                parent::__construct('pimcore:classificationstore:backfill-identifiers');
            }

            protected function createBackfill(): IdentifierBackfill
            {
                return $this->backfill;
            }
        };

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertSame(
            'assigned-meanwhile',
            $this->fetchIdentifier(self::TABLE_KEYS, $keyId),
            'The backfill must not overwrite an identifier assigned since the pre-flight'
        );

        // reported as unresolved, so it is not part of the backfilled tally
        $this->assertStringContainsString(
            sprintf('key %d "Volume": an identifier was assigned while the command was running', $keyId),
            $tester->getDisplay()
        );
    }

    public function testBackfillTouchesOnlyIdentifier(): void
    {
        $storeId = $this->seedStore();
        $keyId = $this->seedKey($storeId, 'Weight', true);

        $db = Db::get();
        $db->update(
            self::TABLE_KEYS,
            ['title' => 'A title the definition does not know', 'definition' => '{"title":"Something else"}'],
            ['id' => $keyId]
        );
        $this->seedRow(self::TABLE_GROUPS, $storeId, 'Untouched Group');
        $this->seedRow(self::TABLE_COLLECTIONS, $storeId, 'Untouched Collection');
        $this->seedRow(self::TABLE_SUBGROUPS, $storeId, 'Untouched Sub Group');
        $identifiedId = $this->seedKey($storeId, 'Identified', true);
        $db->update(self::TABLE_KEYS, ['identifier' => self::PRESET_IDENTIFIER], ['id' => $identifiedId]);

        $before = $this->snapshot();
        $this->runCommand();
        $after = $this->snapshot();

        foreach (self::TABLES as $table) {
            $this->assertCount(count($before[$table]), $after[$table], $table . ' must not gain or lose rows');

            foreach ($before[$table] as $index => $beforeRow) {
                $afterRow = $after[$table][$index];
                unset($beforeRow['identifier'], $afterRow['identifier']);

                $this->assertSame($beforeRow, $afterRow, $table . ' must only change the identifier column');
            }
        }

        $this->assertNotNull($this->fetchIdentifier(self::TABLE_KEYS, $keyId));
        $this->assertSame(self::PRESET_IDENTIFIER, $this->fetchIdentifier(self::TABLE_KEYS, $identifiedId));
        $this->assertSame(
            $before[self::TABLE_KEYS][array_search($identifiedId, array_column($before[self::TABLE_KEYS], 'id'))],
            $after[self::TABLE_KEYS][array_search($identifiedId, array_column($after[self::TABLE_KEYS], 'id'))],
            'A row that already had an identifier must be byte-identical'
        );
    }

    public function testCachedConfigsAreInvalidated(): void
    {
        $storeId = $this->seedStore();
        $keyId = $this->seedKey($storeId, 'Cached Key', true);
        $groupId = $this->seedRow(self::TABLE_GROUPS, $storeId, 'Cached Group');
        $collectionId = $this->seedRow(self::TABLE_COLLECTIONS, $storeId, 'Cached Collection');

        $this->assertNull(KeyConfig::getById($keyId)?->getIdentifier());
        $this->assertNull(GroupConfig::getById($groupId)?->getIdentifier());
        $this->assertNull(CollectionConfig::getById($collectionId)?->getIdentifier());

        $this->runCommand();

        $this->assertNotNull(KeyConfig::getById($keyId)?->getIdentifier());
        $this->assertNotNull(GroupConfig::getById($groupId)?->getIdentifier());
        $this->assertNotNull(CollectionConfig::getById($collectionId)?->getIdentifier());
    }

    public function testBackfillIsIdempotent(): void
    {
        $storeId = $this->seedStore();
        $this->seedKey($storeId, 'Length', true);
        $this->seedKey($storeId, 'Depth', true);
        $this->seedKey($storeId, 'Depth', true);
        $this->seedRow(self::TABLE_SUBGROUPS, $storeId, 'Idempotent Sub Group');

        $firstRun = $this->runCommand();
        $afterFirstRun = $this->snapshot();

        $secondRun = $this->runCommand();
        $afterSecondRun = $this->snapshot();

        $this->assertSame($afterFirstRun, $afterSecondRun, 'A second run must not change a single row');

        $this->assertSame([], $this->unresolvedLines($firstRun->getDisplay()));
        $this->assertSame([], $this->unresolvedLines($secondRun->getDisplay()));
        $this->assertStringContainsString('0 row(s) backfilled.', $secondRun->getDisplay());
    }

    private function runCommand(): CommandTester
    {
        $tester = new CommandTester(new BackfillClassificationStoreIdentifiersCommand());
        $tester->execute([]);

        return $tester;
    }

    private function snapshot(): array
    {
        $db = Db::get();
        $snapshot = [];

        foreach (self::TABLES as $table) {
            $snapshot[$table] = $db->fetchAllAssociative('SELECT * FROM ' . $table . ' ORDER BY id');
        }

        return $snapshot;
    }

    private function unresolvedLines(string $display): array
    {
        $lines = [];

        foreach (explode("\n", $display) as $line) {
            $line = trim($line);
            if (preg_match('/^(store|collection|group|subgroup|key) \d+ "/', $line) === 1) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    private function countNullIdentifiers(int $storeId): int
    {
        $db = Db::get();
        $count = (int) $db->fetchOne(
            'SELECT COUNT(*) FROM ' . self::TABLE_STORES . ' WHERE id = ? AND identifier IS NULL',
            [$storeId]
        );

        foreach ([self::TABLE_COLLECTIONS, self::TABLE_GROUPS, self::TABLE_SUBGROUPS, self::TABLE_KEYS] as $table) {
            $count += (int) $db->fetchOne(
                'SELECT COUNT(*) FROM ' . $table . ' WHERE storeId = ? AND identifier IS NULL',
                [$storeId]
            );
        }

        return $count;
    }

    private function assertGeneratedIdentifiersDiffer(string $table, array $ids, array $names): void
    {
        $identifiers = array_map(fn (int $id) => $this->fetchIdentifier($table, $id), $ids);

        $this->assertCount(count($ids), array_unique($identifiers), 'Every row must get its own identifier');

        foreach ($identifiers as $identifier) {
            $this->assertNotNull($identifier);
            $this->assertMatchesRegularExpression(self::IDENTIFIER_PATTERN, $identifier);
            $this->assertLessThanOrEqual(190, strlen($identifier));

            foreach ($names as $name) {
                $this->assertStringNotContainsStringIgnoringCase($name, $identifier);
            }
        }
    }

    private function assertDerivedIdentifier(string $table, int $id): void
    {
        $identifier = $this->fetchIdentifier($table, $id);

        $this->assertNotNull($identifier, $table . ' row ' . $id . ' must be backfilled');
        $this->assertMatchesRegularExpression(self::IDENTIFIER_PATTERN, $identifier);
    }

    private function seedStore(): int
    {
        $db = Db::get();
        $db->insert(self::TABLE_STORES, ['name' => uniqid('backfillstore', true)]);
        $storeId = (int) $db->lastInsertId();

        $this->createdStoreIds[] = $storeId;

        return $storeId;
    }

    private function seedKey(int $storeId, string $name, bool $enabled): int
    {
        $db = Db::get();
        $db->insert(self::TABLE_KEYS, [
            'storeId' => $storeId,
            'name' => $name,
            'type' => 'input',
            'enabled' => $enabled ? 1 : 0,
        ]);

        return (int) $db->lastInsertId();
    }

    private function seedRow(string $table, int $storeId, string $name): int
    {
        $db = Db::get();
        $db->insert($table, ['storeId' => $storeId, 'name' => $name]);

        return (int) $db->lastInsertId();
    }

    private function fetchIdentifier(string $table, int $id): ?string
    {
        $identifier = Db::get()->fetchOne('SELECT identifier FROM ' . $table . ' WHERE id = ?', [$id]);

        return $identifier === false || $identifier === null ? null : (string) $identifier;
    }
}
