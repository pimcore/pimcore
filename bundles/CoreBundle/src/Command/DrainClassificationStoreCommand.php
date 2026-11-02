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

use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\DocumentEngineMigrationService;
use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\DrainOutcome;
use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\DrainPairResult;
use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Traits\DryRun;
use Pimcore\Console\Traits\Parallelization;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:classificationstore:drain',
    description: 'Convert the EAV rows of a Classification Store on the document engine into documents',
    aliases: ['classificationstore:drain']
)]
class DrainClassificationStoreCommand extends AbstractCommand
{
    use DryRun;
    use Parallelization {
        execute as runParallelized;
        runAfterBatch as collectGarbageAfterBatch;
    }

    /**
     * The unit of work is one (objectId, fieldname) pair, which travels as one item through the
     * parallelization. Neither part can contain the separator: a class id and a field name are
     * both identifiers.
     */
    private const ITEM_SEPARATOR = '~~~';

    private const DEFAULT_MAX_DIVERGENCES = 1;

    private const BACKLOG_LINE = '%s: %d pair(s) to convert';

    private int $maxDivergences = self::DEFAULT_MAX_DIVERGENCES;

    private int $migratedAt = 0;

    private float $startedAt = 0.0;

    private int $backlogSize = 0;

    private int $converted = 0;

    private int $alreadyMigrated = 0;

    /**
     * @var array<string, string[]> class id => the pairs that did not compare equal
     */
    private array $divergences = [];

    /**
     * @var array<string, bool> the classes whose remaining pairs are skipped
     */
    private array $haltedClasses = [];

    public function __construct(private readonly DocumentEngineMigrationService $migrationService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();
        self::configureCommand($this);

        $this
            ->addOption(
                'class',
                null,
                InputOption::VALUE_REQUIRED,
                'Only convert the values of this class, instead of every class holding converted values'
            )
            ->addOption(
                'max-divergences',
                null,
                InputOption::VALUE_REQUIRED,
                'How many diverging pairs a class may report before its remaining pairs are skipped',
                (string) self::DEFAULT_MAX_DIVERGENCES
            )
            ->configureDryRunOption('Report the backlog of every class and convert nothing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->isDryRun()) {
            return $this->reportBacklog();
        }

        $this->maxDivergences = max((int) $input->getOption('max-divergences'), 1);
        $this->migratedAt = time();
        $this->startedAt = microtime(true);

        // the divergence threshold is per class, so the pairs of one class have to be counted by
        // one process. An operator asking for --processes gets the throughput and a threshold per
        // worker instead.
        if ($input->hasOption('main-process') && $input->getOption('processes') === null) {
            $input->setOption('main-process', true);
        }

        try {
            $status = $this->runParallelized($input, $output);
        } finally {
            // the run lock is released by runAfterLastCommand(), which a failure on the way to the
            // first pair never reaches
            $this->release();
        }

        $this->report();

        return $this->haltedClasses === [] ? $status : self::FAILURE;
    }

    /**
     * @return string[]
     */
    protected function fetchItems(InputInterface $input, OutputInterface $output): array
    {
        $items = [];

        foreach ($this->migrationService->getBacklogByClass($this->getClassId($input)) as $classId => $pairs) {
            // a class id is numeric by default, and PHP makes an array key of such a string an
            // int - which every method taking a class id would refuse under strict types
            $classId = (string) $classId;

            $this->io->writeln(sprintf(self::BACKLOG_LINE, $classId, count($pairs)));

            // a store flipped by hand has no document table, and the first write of this class
            // would be the one to find that out
            $class = $this->migrationService->getClassDefinition($classId);
            if ($class) {
                $this->migrationService->createDocumentTable($class);
            }

            foreach ($pairs as [$objectId, $fieldname]) {
                $items[] = implode(self::ITEM_SEPARATOR, [$classId, $objectId, $fieldname]);
            }
        }

        $this->backlogSize = count($items);

        return $items;
    }

    protected function runSingleCommand(string $item, InputInterface $input, OutputInterface $output): void
    {
        [$classId, $objectId, $fieldname] = explode(self::ITEM_SEPARATOR, $item, 3);

        if (isset($this->haltedClasses[$classId])) {
            return;
        }

        $class = $this->migrationService->getClassDefinition($classId);
        if (!$class) {
            $this->writeError('No class definition found for ' . $classId);

            return;
        }

        $result = $this->migrationService->drainPair($class, (int) $objectId, $fieldname, $this->migratedAt);

        match ($result->outcome) {
            DrainOutcome::Converted => $this->converted++,
            DrainOutcome::AlreadyMigrated => $this->alreadyMigrated++,
            DrainOutcome::Diverged => $this->recordDivergence($classId, $result),
        };
    }

    /**
     * @param string[] $items
     */
    protected function runAfterBatch(InputInterface $input, OutputInterface $output, array $items): void
    {
        $this->collectGarbageAfterBatch($input, $output, $items);
        $this->reportProgress($output);
    }

    protected function getItemName(?int $count): string
    {
        return $count === 1 ? 'pair' : 'pairs';
    }

    /**
     * The backlog of every class, without converting anything.
     */
    private function reportBacklog(): int
    {
        $backlog = $this->migrationService->getBacklogByClass($this->getClassId($this->input));

        if ($backlog === []) {
            $this->io->writeln($this->dryRunMessage('No class holds EAV rows for a field on the document engine.'));

            return self::SUCCESS;
        }

        foreach ($backlog as $classId => $pairs) {
            $this->io->writeln($this->dryRunMessage(sprintf(self::BACKLOG_LINE, $classId, count($pairs))));
        }

        return self::SUCCESS;
    }

    private function recordDivergence(string $classId, DrainPairResult $result): void
    {
        $this->divergences[$classId][] = $result->describe();
        $this->io->writeln(sprintf('<error>divergence</error> %s: %s', $classId, $result->describe()));

        if (count($this->divergences[$classId]) < $this->maxDivergences) {
            return;
        }

        $this->haltedClasses[$classId] = true;
        $this->io->writeln(sprintf(
            '<error>%s halted</error> after %d divergence(s); continuing with the next class',
            $classId,
            count($this->divergences[$classId])
        ));
    }

    /**
     * What the run is achieving, from its own measured rate rather than from a figure of ours: how
     * many pairs it has converted, how long that took, the rate that works out to and how long the
     * pairs that are left would take at it.
     */
    private function reportProgress(OutputInterface $output): void
    {
        if ($this->backlogSize === 0) {
            return;
        }

        $elapsed = max(microtime(true) - $this->startedAt, 0.001);
        $done = $this->converted + $this->alreadyMigrated;
        $rate = $done / $elapsed;
        $remaining = max($this->backlogSize - $done - $this->countDivergences(), 0);

        $output->writeln(sprintf(
            '%d of %d pair(s) converted in %s, rate %s pair(s)/s, estimated %s remaining',
            $done,
            $this->backlogSize,
            $this->formatDuration($elapsed),
            number_format($rate, 2),
            $rate > 0.0 ? $this->formatDuration($remaining / $rate) : 'an unknown time'
        ));
    }

    private function report(): void
    {
        $this->reportProgress($this->output);

        if ($this->divergences === []) {
            $this->io->success(sprintf(
                '%d pair(s) converted, %d already migrated.',
                $this->converted,
                $this->alreadyMigrated
            ));

            return;
        }

        $this->io->section('Pairs that did not compare equal and were left on EAV');
        foreach ($this->divergences as $classId => $pairs) {
            foreach ($pairs as $pair) {
                $this->io->writeln($classId . ': ' . $pair);
            }
        }

        $this->io->error(sprintf(
            '%d pair(s) diverged, %d class(es) halted: %s',
            $this->countDivergences(),
            count($this->haltedClasses),
            implode(', ', array_keys($this->haltedClasses))
        ));
    }

    private function countDivergences(): int
    {
        return array_sum(array_map(count(...), $this->divergences));
    }

    private function formatDuration(float $seconds): string
    {
        if ($seconds < 60.0) {
            return number_format($seconds, 1) . 's';
        }

        return sprintf('%dm %ds', (int) ($seconds / 60), (int) fmod($seconds, 60.0));
    }

    private function getClassId(InputInterface $input): ?string
    {
        $classId = $input->getOption('class');

        return is_string($classId) && $classId !== '' ? $classId : null;
    }
}
