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

namespace Pimcore\Bundle\CoreBundle\Command\Asset;

use DateTimeImmutable;
use Pimcore\Asset\StorageQueue\StorageOperationQueueRepositoryInterface;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:assets:storage-queue:status',
    description: 'Show pending asset storage operations and warn about stale rows'
)]
final class StorageQueueStatusCommand extends AbstractCommand
{
    public function __construct(
        private readonly StorageOperationQueueRepositoryInterface $repository,
        private readonly bool $enabled,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('warn-age', null, InputOption::VALUE_REQUIRED, 'Warn (exit 1) when a row is older than this many hours', '48');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $operations = $this->repository->all();
        if ($operations === []) {
            $output->writeln('The storage operation queue is empty.');

            return self::SUCCESS;
        }

        $now = new DateTimeImmutable();
        $warnAgeHours = (int) $input->getOption('warn-age');
        $staleRows = 0;

        $table = new Table($output);
        $table->setHeaders(['id', 'storage', 'operation', 'source prefix', 'target prefix', 'age (h)']);
        foreach ($operations as $operation) {
            $ageHours = ($now->getTimestamp() - $operation->getCreatedAt()->getTimestamp()) / 3600;
            if ($ageHours > $warnAgeHours) {
                $staleRows++;
            }
            $table->addRow([
                $operation->getId(),
                $operation->getStorage(),
                $operation->getType()->value,
                $operation->getSourcePrefix(),
                $operation->getTargetPrefix() ?? '-',
                sprintf('%.1f', $ageHours),
            ]);
        }
        $table->render();

        $failure = false;
        if ($staleRows > 0) {
            $this->writeError(sprintf(
                '%d row(s) older than %dh - is the storage-queue:process cron scheduled?',
                $staleRows,
                $warnAgeHours
            ));
            $failure = true;
        }
        if (!$this->enabled) {
            $this->writeError(
                'Pending operations exist but the feature is disabled (pimcore.assets.storage_operation_queue.enabled) - '
                . 'content under moved prefixes is unreachable at its logical paths until re-enabled and processed.'
            );
            $failure = true;
        }

        return $failure ? self::FAILURE : self::SUCCESS;
    }
}
