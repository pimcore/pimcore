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
use Pimcore\Db;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Tests\Support\Test\ModelTestCase;
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

    private const TABLES = [
        self::TABLE_STORES,
        self::TABLE_COLLECTIONS,
        self::TABLE_GROUPS,
        self::TABLE_KEYS,
    ];

    private const IDENTIFIER_PATTERN = '/\A[A-Za-z0-9][A-Za-z0-9_-]*\z/';

    private array $createdStoreIds = [];

    protected function tearDown(): void
    {
        $db = Db::get();

        foreach ($this->createdStoreIds as $storeId) {
            foreach ([self::TABLE_COLLECTIONS, self::TABLE_GROUPS, self::TABLE_KEYS] as $table) {
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

        $this->runCommand();

        $this->assertDerivedIdentifier(self::TABLE_STORES, $storeId);
        $this->assertDerivedIdentifier(self::TABLE_KEYS, $enabledKeyId);
        $this->assertDerivedIdentifier(self::TABLE_KEYS, $disabledKeyId);
        $this->assertDerivedIdentifier(self::TABLE_GROUPS, $groupId);
        $this->assertDerivedIdentifier(self::TABLE_COLLECTIONS, $collectionId);

        $this->assertSame(
            0,
            (int) Db::get()->fetchOne(
                'SELECT enabled FROM ' . self::TABLE_KEYS . ' WHERE id = ?',
                [$disabledKeyId]
            ),
            'The disabled key must stay disabled'
        );
    }

    public function testUnresolvedRowsAreReportedAndExitZero(): void
    {
        $storeId = $this->seedStore();
        $firstHeightId = $this->seedKey($storeId, 'Height', true);
        $secondHeightId = $this->seedKey($storeId, 'Height', true);
        $namelessId = $this->seedKey($storeId, '...', true);
        $widthId = $this->seedKey($storeId, 'Width', true);

        $tester = $this->runCommand();

        $this->assertSame(0, $tester->getStatusCode());

        foreach ([$firstHeightId, $secondHeightId, $namelessId] as $id) {
            $this->assertNull($this->fetchIdentifier(self::TABLE_KEYS, $id));
        }

        $this->assertSame('width', $this->fetchIdentifier(self::TABLE_KEYS, $widthId));

        $display = $tester->getDisplay();
        $this->assertStringContainsString(
            sprintf('key %d "Height": the name is not unique within its store', $firstHeightId),
            $display
        );
        $this->assertStringContainsString(
            sprintf('key %d "Height": the name is not unique within its store', $secondHeightId),
            $display
        );
        $this->assertStringContainsString(
            sprintf('key %d "...": the name yields no valid identifier', $namelessId),
            $display
        );
    }

    public function testADuplicateNameIsReportedEvenWhenTheOtherRowIsAlreadyIdentified(): void
    {
        $storeId = $this->seedStore();
        $identifiedHeightId = $this->seedKey($storeId, 'Height', true);
        $unidentifiedHeightId = $this->seedKey($storeId, 'Height', true);
        Db::get()->update(self::TABLE_KEYS, ['identifier' => '01JA0PQ7XK'], ['id' => $identifiedHeightId]);

        $tester = $this->runCommand();

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertSame('01JA0PQ7XK', $this->fetchIdentifier(self::TABLE_KEYS, $identifiedHeightId));
        $this->assertNull(
            $this->fetchIdentifier(self::TABLE_KEYS, $unidentifiedHeightId),
            'A name shared with an already identified row must not be backfilled'
        );
        $this->assertStringContainsString(
            sprintf('key %d "Height": the name is not unique within its store', $unidentifiedHeightId),
            $tester->getDisplay()
        );
    }

    public function testAnIdentifierAssignedAfterThePreflightIsPreservedAndNotCounted(): void
    {
        $storeId = $this->seedStore();
        $keyId = $this->seedKey($storeId, 'Volume', true);

        $command = new class($keyId) extends BackfillClassificationStoreIdentifiersCommand {
            public function __construct(private readonly int $targetId)
            {
                // the AsCommand attribute is not inherited by an anonymous subclass
                parent::__construct('pimcore:classificationstore:backfill-identifiers');
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

        $this->assertSame('weight', $this->fetchIdentifier(self::TABLE_KEYS, $keyId));
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

        $firstRun = $this->runCommand();
        $afterFirstRun = $this->snapshot();

        $secondRun = $this->runCommand();
        $afterSecondRun = $this->snapshot();

        $this->assertSame($afterFirstRun, $afterSecondRun, 'A second run must not change a single row');

        $unresolved = $this->unresolvedLines($firstRun->getDisplay());
        $this->assertNotEmpty($unresolved);
        $this->assertSame($unresolved, $this->unresolvedLines($secondRun->getDisplay()));
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
            if (preg_match('/^(store|collection|group|key) \d+ "/', $line) === 1) {
                $lines[] = $line;
            }
        }

        return $lines;
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
