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

namespace Pimcore\Bundle\CoreBundle\Command;

use Doctrine\DBAL\Connection;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Console\AbstractCommand;
use Pimcore\Db;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Uid\Ulid;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:classificationstore:backfill-identifiers',
    description: 'Assign a generated identifier to classification store rows which do not have one yet'
)]
class BackfillClassificationStoreIdentifiersCommand extends AbstractCommand
{
    private const TABLE_STORES = 'classificationstore_stores';

    private const TABLES = [
        'store' => self::TABLE_STORES,
        'collection' => 'classificationstore_collections',
        'group' => 'classificationstore_groups',
        'subgroup' => 'classificationstore_subgroups',
        'key' => 'classificationstore_keys',
    ];

    private const REASON_ASSIGNED_MEANWHILE = 'an identifier was assigned while the command was running';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        [$written, $unresolved] = $this->backfill(Db::get());

        $this->report($written, $unresolved);

        return 0;
    }

    /**
     * Shared with the migration that runs the backfill on upgrade.
     *
     * @return array{0: int, 1: array} the number of rows written and the rows left without an identifier
     */
    public function backfill(Connection $db): array
    {
        $plan = [];
        foreach (self::TABLES as $type => $table) {
            $plan[$type] = $this->resolve($this->loadRows($db, $table));
        }

        $written = 0;
        $unresolved = [];
        foreach ($plan as $type => $assignments) {
            foreach ($assignments as $assignment) {
                if ($this->write($db, self::TABLES[$type], $assignment)) {
                    $written++;

                    continue;
                }

                // the guard also matches nothing for a row deleted since it was read; that row needs no identifier
                if ($this->rowExists($db, self::TABLES[$type], $assignment['id'])) {
                    $unresolved[] = ['type' => $type] + $this->failure($assignment, self::REASON_ASSIGNED_MEANWHILE);
                }
            }
        }

        if ($written > 0) {
            RuntimeCache::clear();
            Cache::clearAll();
        }

        return [$written, $unresolved];
    }

    private function loadRows(Connection $db, string $table): array
    {
        return $db->fetchAllAssociative(
            'SELECT `id`, `name` FROM `' . $table . '` WHERE `identifier` IS NULL ORDER BY `id`'
        );
    }

    private function resolve(array $rows): array
    {
        $assignments = [];

        foreach ($rows as $row) {
            $assignments[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'identifier' => Ulid::generate(),
            ];
        }

        return $assignments;
    }

    /**
     * `identifier IS NULL` makes the write lose against an identifier assigned since the rows were read,
     * rather than replace one that is already immutable.
     */
    protected function write(Connection $db, string $table, array $assignment): bool
    {
        $affected = $db->executeStatement(
            'UPDATE `' . $table . '` SET `identifier` = ? WHERE `id` = ? AND `identifier` IS NULL',
            [$assignment['identifier'], $assignment['id']]
        );

        return $affected > 0;
    }

    private function rowExists(Connection $db, string $table, int $id): bool
    {
        return $db->fetchOne('SELECT 1 FROM `' . $table . '` WHERE `id` = ?', [$id]) !== false;
    }

    /**
     * The one line describing a row left without an identifier, shared by the command report and the migration log.
     */
    public static function formatUnresolved(array $row): string
    {
        return sprintf('%s %d "%s": %s', $row['type'], $row['id'], $row['name'], $row['reason']);
    }

    private function failure(array $row, string $reason): array
    {
        return ['id' => (int) $row['id'], 'name' => (string) $row['name'], 'reason' => $reason];
    }

    private function report(int $written, array $unresolved): void
    {
        $this->io->writeln(sprintf('%d row(s) backfilled.', $written));

        if ($unresolved === []) {
            $this->io->success('Every classification store row carries an identifier.');

            return;
        }

        $this->io->section('Rows left without an identifier');
        foreach ($unresolved as $row) {
            $this->io->writeln(self::formatUnresolved($row));
        }
    }
}
