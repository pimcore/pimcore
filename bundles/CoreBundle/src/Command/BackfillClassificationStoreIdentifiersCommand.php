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
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
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

    private const REASON_ASSIGNED_MEANWHILE = 'an identifier was assigned while the command was running';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $db = Db::get();
        $plan = [];
        $unresolved = [];

        foreach (self::TABLES as $type => $table) {
            [$plan[$type], $failures] = $this->resolve($this->loadRows($db, $table));

            foreach ($failures as $failure) {
                $unresolved[] = ['type' => $type] + $failure;
            }
        }

        $written = 0;
        foreach ($plan as $type => $assignments) {
            foreach ($assignments as $assignment) {
                if ($this->write($db, self::TABLES[$type], $assignment)) {
                    $written++;

                    continue;
                }

                $unresolved[] = ['type' => $type] + $this->failure($assignment, self::REASON_ASSIGNED_MEANWHILE);
            }
        }

        if ($written > 0) {
            RuntimeCache::clear();
            Cache::clearAll();
        }

        $this->report($written, $unresolved);

        return 0;
    }

    /**
     * Every row of the table, identified or not: a name clash is a clash against the whole store, not only
     * against the rows this command may write to.
     */
    private function loadRows(Connection $db, string $table): array
    {
        return $db->fetchAllAssociative(
            'SELECT `id`, `name`, `identifier`, ' . $this->storeIdExpression($table) . ' AS `storeId`'
            . ' FROM `' . $table . '` ORDER BY `id`'
        );
    }

    private function storeIdExpression(string $table): string
    {
        return $table === self::TABLE_STORES ? '0' : '`storeId`';
    }

    /**
     * Pre-flight: decides for every row whether a unique identifier can be derived, before anything is written.
     */
    private function resolve(array $rows): array
    {
        $taken = [];
        $namesInScope = [];

        foreach ($rows as $row) {
            $scope = (int) $row['storeId'];

            if ($row['identifier'] !== null) {
                $taken[$scope][strtolower((string) $row['identifier'])] = true;
            }

            $derived = $this->deriveIdentifier($row['name']);
            if ($derived !== null) {
                $namesInScope[$scope][$derived] = ($namesInScope[$scope][$derived] ?? 0) + 1;
            }
        }

        $assignments = [];
        $failures = [];

        foreach ($rows as $row) {
            if ($row['identifier'] !== null) {
                continue;
            }

            $scope = (int) $row['storeId'];
            $identifier = $this->deriveIdentifier($row['name']);

            if ($identifier === null) {
                $failures[] = $this->failure($row, self::REASON_NO_USABLE_NAME);
            } elseif ($namesInScope[$scope][$identifier] > 1) {
                $failures[] = $this->failure($row, self::REASON_DUPLICATE_NAME);
            } elseif (isset($taken[$scope][$identifier])) {
                $failures[] = $this->failure($row, self::REASON_ALREADY_IN_USE);
            } else {
                $assignments[] = [
                    'id' => (int) $row['id'],
                    'name' => (string) $row['name'],
                    'identifier' => $identifier,
                ];
            }
        }

        return [$assignments, $failures];
    }

    /**
     * `identifier IS NULL` makes the write lose against an identifier assigned since the pre-flight read,
     * rather than replace one that is already immutable.
     */
    protected function write(Connection $db, string $table, array $assignment): bool
    {
        try {
            $affected = $db->executeStatement(
                'UPDATE `' . $table . '` SET `identifier` = ? WHERE `id` = ? AND `identifier` IS NULL',
                [$assignment['identifier'], $assignment['id']]
            );
        } catch (UniqueConstraintViolationException) {
            // another process claimed the derived identifier between the pre-flight and here
            return false;
        }

        return $affected > 0;
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
