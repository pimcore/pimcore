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

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:classificationstore:backfill-identifiers',
    description: 'Assign a name derived identifier to classification store rows which do not have one yet'
)]
class BackfillClassificationStoreIdentifiersCommand extends AbstractCommand
{
    private const TABLE_STORES = 'classificationstore_stores';

    private const TABLES = [
        'store' => self::TABLE_STORES,
        'collection' => 'classificationstore_collections',
        'group' => 'classificationstore_groups',
        'key' => 'classificationstore_keys',
    ];

    private const IDENTIFIER_MAX_LENGTH = 190;

    private const REASON_NO_USABLE_NAME = 'the name yields no valid identifier';

    private const REASON_DUPLICATE_NAME = 'the name is not unique within its store';

    private const REASON_ALREADY_IN_USE = 'the derived identifier is already in use';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $db = Db::get();
        $plan = [];
        $unresolved = [];

        foreach (self::TABLES as $type => $table) {
            [$plan[$table], $failures] = $this->resolve(
                $this->loadRowsWithoutIdentifier($db, $table),
                $this->loadTakenIdentifiers($db, $table)
            );

            foreach ($failures as $failure) {
                $unresolved[] = ['type' => $type] + $failure;
            }
        }

        $written = 0;
        foreach ($plan as $table => $assignments) {
            foreach ($assignments as $id => $identifier) {
                $db->update($table, ['identifier' => $identifier], ['id' => $id]);
            }
            $written += count($assignments);
        }

        if ($written > 0) {
            RuntimeCache::clear();
            Cache::clearAll();
        }

        $this->report($written, $unresolved);

        return 0;
    }

    private function loadRowsWithoutIdentifier(Connection $db, string $table): array
    {
        return $db->fetchAllAssociative(
            'SELECT `id`, `name`, ' . $this->storeIdExpression($table) . ' AS `storeId` FROM `' . $table . '`'
            . ' WHERE `identifier` IS NULL ORDER BY `id`'
        );
    }

    private function loadTakenIdentifiers(Connection $db, string $table): array
    {
        $rows = $db->fetchAllAssociative(
            'SELECT `identifier`, ' . $this->storeIdExpression($table) . ' AS `storeId` FROM `' . $table . '`'
            . ' WHERE `identifier` IS NOT NULL'
        );

        $taken = [];
        foreach ($rows as $row) {
            $taken[(int) $row['storeId']][strtolower((string) $row['identifier'])] = true;
        }

        return $taken;
    }

    private function storeIdExpression(string $table): string
    {
        return $table === self::TABLE_STORES ? '0' : '`storeId`';
    }

    /**
     * Pre-flight: decides for every row whether a unique identifier can be derived, before anything is written.
     */
    private function resolve(array $rows, array $taken): array
    {
        $candidates = [];
        $failures = [];

        foreach ($rows as $row) {
            $identifier = $this->deriveIdentifier($row['name']);
            if ($identifier === null) {
                $failures[] = $this->failure($row, self::REASON_NO_USABLE_NAME);

                continue;
            }

            $scope = (int) $row['storeId'];
            if (isset($taken[$scope][$identifier])) {
                $failures[] = $this->failure($row, self::REASON_ALREADY_IN_USE);

                continue;
            }

            $candidates[$scope][$identifier][] = $row;
        }

        [$assignments, $duplicates] = $this->pickUniqueCandidates($candidates);

        return [$assignments, [...$failures, ...$duplicates]];
    }

    /**
     * A derived identifier claimed by more than one row in the same scope resolves none of them.
     */
    private function pickUniqueCandidates(array $candidates): array
    {
        $assignments = [];
        $failures = [];

        foreach ($candidates as $byIdentifier) {
            foreach ($byIdentifier as $identifier => $rowsForIdentifier) {
                if (count($rowsForIdentifier) > 1) {
                    foreach ($rowsForIdentifier as $row) {
                        $failures[] = $this->failure($row, self::REASON_DUPLICATE_NAME);
                    }

                    continue;
                }

                $assignments[(int) $rowsForIdentifier[0]['id']] = (string) $identifier;
            }
        }

        return [$assignments, $failures];
    }

    private function deriveIdentifier(?string $name): ?string
    {
        $identifier = strtolower((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $name));
        $identifier = substr(ltrim($identifier, '-_'), 0, self::IDENTIFIER_MAX_LENGTH);

        return $identifier === '' ? null : $identifier;
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
            $this->io->writeln(
                sprintf('%s %d "%s": %s', $row['type'], $row['id'], $row['name'], $row['reason'])
            );
        }
    }
}
