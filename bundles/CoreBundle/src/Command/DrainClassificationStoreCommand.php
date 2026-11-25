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

use Exception;
use Pimcore;
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

    /**
     * How many converted pairs the runtime cache is allowed to accumulate.
     *
     * Every pair loads its object, and a loaded object stays registered in the runtime cache -
     * {@see \Pimcore\Model\DataObject\AbstractObject::getById()} registers it even for a forced
     * load - so a drain of a whole installation would hold every object it touched. The
     * parallelization clears the cache once a batch, but the fallback used when the optional
     * parallelization package is absent has no batches at all: it hands the whole item stream to
     * the after-batch hook once, at the end, {@see \Pimcore\Console\Traits\ParallelizationBase}.
     * Counting the pairs here bounds the cache in either case.
     */
    public const GARBAGE_INTERVAL = 100;

    private int $maxDivergences = self::DEFAULT_MAX_DIVERGENCES;

    private int $migratedAt = 0;

    private float $startedAt = 0.0;

    private int $backlogSize = 0;

    private int $converted = 0;

    private int $alreadyMigrated = 0;

    private int $pairsSinceGarbageCollection = 0;

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
        // before either execution path: a class id that resolves to nothing contributes no backlog,
        // so a dry run would report none and a real run would convert nothing and succeed - which is
        // what would let a mistyped class id in a migration script pass for a drained class. The
        // verify and the cleanup command refuse the same way
        $classId = $this->getClassId($input);
        if ($classId !== null && $this->migrationService->getClasses($classId) === []) {
            $this->io->error('No class definition found for ' . $classId);

            return self::FAILURE;
        }

        if ($this->isDryRun()) {
            return $this->reportBacklog();
        }

        $this->maxDivergences = max((int) $input->getOption('max-divergences'), 1);
        $this->migratedAt = time();
        $this->startedAt = microtime(true);

        // the divergence threshold is per class, so the pairs of one class have to be counted by
        // one process. An operator asking for --processes gets the throughput and a threshold per
        // worker instead - and a report of the run read back from the backlog rather than from
        // counters the workers hold, {@see self::report()}.
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

        $remaining = $this->report();

        return $this->haltedClasses === [] && $remaining === 0 ? $status : self::FAILURE;
    }

    /**
     * The pairs to convert, read as they are converted rather than collected first.
     *
     * The order a run goes in has to be known before it starts, and the backlog of a whole
     * installation does not fit in the process this command is one of - so what is read up front is
     * one count per class, and the pairs of a class are streamed from its value tables a bounded
     * batch at a time, {@see DocumentEngineMigrationService::streamBacklog()}. The parallelization
     * contract takes any iterable, and caches the item strings it has handed out so that it can
     * rewind; nothing of a pair but that string is kept.
     *
     * @return iterable<string>
     */
    protected function fetchItems(InputInterface $input, OutputInterface $output): iterable
    {
        $sizes = $this->migrationService->getBacklogSizeByClass($this->getClassId($input));
        $this->backlogSize = array_sum($sizes);

        foreach ($sizes as $classId => $size) {
            // a class id is numeric by default, and PHP makes an array key of such a string an
            // int - which every method taking a class id would refuse under strict types
            $classId = (string) $classId;

            $this->io->writeln(sprintf(self::BACKLOG_LINE, $classId, $size));

            $class = $this->migrationService->getClassDefinition($classId);
            if (!$class) {
                continue;
            }

            // a store flipped by hand has no document table, and the first write of this class
            // would be the one to find that out
            $this->migrationService->createDocumentTable($class);

            foreach ($this->migrationService->streamBacklog($class) as [$objectId, $fieldname]) {
                yield implode(self::ITEM_SEPARATOR, [$classId, $objectId, $fieldname]);
            }
        }
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

        $this->recordOutcome($classId, $result);

        $this->collectGarbageAfterInterval($output);
    }

    /**
     * Counts what became of one pair.
     *
     * The count belongs to the process that converted the pair: with `--processes` that is a
     * worker, and none of it reaches the process that reports the run - which is why the report
     * reads its totals back from the backlog instead, {@see self::report()}.
     */
    protected function recordOutcome(string $classId, DrainPairResult $result): void
    {
        match ($result->outcome) {
            DrainOutcome::Converted => $this->converted++,
            DrainOutcome::AlreadyMigrated => $this->alreadyMigrated++,
            DrainOutcome::Diverged => $this->recordDivergence($classId, $result),
        };
    }

    /**
     * @param iterable<string> $items the parallelization hands this one batch; an install without
     *                                the parallelization package hands it the whole item stream,
     *                                which has been consumed by then and is not an array - nothing
     *                                here reads it either way
     */
    protected function runAfterBatch(InputInterface $input, OutputInterface $output, iterable $items): void
    {
        // the trait's own hook is what collects garbage once a batch; the interval in
        // runSingleCommand() is what bounds the runtime cache when there are no batches to speak of
        $this->collectGarbageAfterBatch($input, $output, is_array($items) ? $items : []);
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
        $sizes = $this->migrationService->getBacklogSizeByClass($this->getClassId($this->input));

        if ($sizes === []) {
            $this->io->writeln($this->dryRunMessage('No class holds EAV rows for a field on the document engine.'));

            return self::SUCCESS;
        }

        foreach ($sizes as $classId => $size) {
            $this->io->writeln($this->dryRunMessage(sprintf(self::BACKLOG_LINE, $classId, $size)));
        }

        return self::SUCCESS;
    }

    /**
     * Clears the runtime cache once every {@see self::GARBAGE_INTERVAL} converted pairs, so that
     * the objects the run has loaded are not held for the length of it.
     */
    private function collectGarbageAfterInterval(OutputInterface $output): void
    {
        $this->pairsSinceGarbageCollection++;

        if ($this->pairsSinceGarbageCollection < self::GARBAGE_INTERVAL) {
            return;
        }

        $this->pairsSinceGarbageCollection = 0;

        if ($output->isVeryVerbose()) {
            $output->writeln(sprintf('Collect garbage after %d pair(s).', self::GARBAGE_INTERVAL));
        }

        Pimcore::collectGarbage();
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

        $done = $this->converted + $this->alreadyMigrated;

        $this->writeProgress(
            $output,
            $done,
            max($this->backlogSize - $done - $this->countDivergences(), 0)
        );
    }

    /**
     * One line of how far the run has got: the pairs behind it, out of the backlog it announced,
     * at the rate those took and what that rate makes of the ones in front of it.
     */
    private function writeProgress(OutputInterface $output, int $done, int $remaining): void
    {
        $elapsed = max(microtime(true) - $this->startedAt, 0.001);
        $rate = $done / $elapsed;

        $output->writeln(sprintf(
            '%d of %d pair(s) converted in %s, rate %s pair(s)/s, estimated %s remaining',
            $done,
            $this->backlogSize,
            $this->formatDuration($elapsed),
            number_format($rate, 2),
            $rate > 0.0 ? $this->formatDuration($remaining / $rate) : 'an unknown time'
        ));
    }

    /**
     * What the run achieved, counted from the EAV rows that are gone rather than from this
     * process's counters.
     *
     * An outcome is counted by the process that converted the pair. With `--processes` that is a
     * worker, and this - the process that discovered the backlog and reports it - converted
     * nothing: its counters would report a successful run as `0 of N`, at a rate of zero. What
     * every process can read back instead is the backlog, which the conversion of a pair empties
     * by the pair it deletes. A run that drained everything leaves none of it, so what is left is
     * also the gate: a worker's divergences and a worker's halted class are both pairs still on
     * EAV, whether or not the process reporting them is the one that found them.
     *
     * @return int the pairs still on EAV, 0 in a process that discovered no backlog of its own
     *
     * @throws Exception
     */
    private function report(): int
    {
        // a worker was handed its items and never counted a backlog; the totals of the run are the
        // discovering process's to print, and it is still running when this one finishes
        $remaining = $this->backlogSize === 0
            ? 0
            : array_sum($this->migrationService->getBacklogSizeByClass($this->getClassId($this->input)));

        if ($this->backlogSize > 0) {
            $this->writeProgress($this->output, max($this->backlogSize - $remaining, 0), $remaining);
        }

        if ($this->divergences !== []) {
            $this->io->section('Pairs that did not compare equal and were left on EAV');
            foreach ($this->divergences as $classId => $pairs) {
                foreach ($pairs as $pair) {
                    $this->io->writeln($classId . ': ' . $pair);
                }
            }
        }

        if ($remaining === 0 && $this->divergences === []) {
            // nothing is left, so the whole backlog is what was drained
            $this->io->success($this->describeDrained($this->backlogSize));

            return 0;
        }

        $this->io->error(implode('; ', $this->describeFailure($remaining)));

        return $remaining;
    }

    /**
     * The breakdown of a successful run, which only the process that converted the pairs has: the
     * two counters add up to the drained pairs there, and to nothing in a process that reports a
     * backlog its workers drained.
     */
    private function describeDrained(int $drained): string
    {
        // a process that discovered no backlog - a worker - reports the pairs it converted itself
        if ($this->backlogSize === 0 || $this->converted + $this->alreadyMigrated === $drained) {
            return sprintf(
                '%d pair(s) converted, %d already migrated.',
                $this->converted,
                $this->alreadyMigrated
            );
        }

        return sprintf('%d pair(s) converted.', $drained);
    }

    /**
     * @return string[]
     */
    private function describeFailure(int $remaining): array
    {
        $messages = [];

        if ($this->divergences !== []) {
            $messages[] = sprintf(
                '%d pair(s) diverged, %d class(es) halted: %s',
                $this->countDivergences(),
                count($this->haltedClasses),
                implode(', ', array_keys($this->haltedClasses))
            );
        }

        if ($remaining > 0) {
            // with --processes the divergences above are a worker's and were reported by it, so
            // this is what the run has to be judged on here
            $messages[] = sprintf('%d pair(s) are still on EAV', $remaining);
        }

        return $messages;
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
