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
use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\DocumentEngineMigrationService;
use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Traits\DryRun;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Classificationstore\DanglingRelationSweep;
use Pimcore\Model\DataObject\Classificationstore\Engine\DocumentEngine;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineFeature;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:classificationstore:purge-relations',
    description: 'Report the Classification Store relations whose target no longer exists, and remove them',
    aliases: ['classificationstore:purge-relations']
)]
class PurgeClassificationStoreRelationsCommand extends AbstractCommand
{
    use DryRun;

    private const OPTION_FORCE = 'force';

    private const OPTION_DRY_RUN = 'dry-run';

    public function __construct(
        private readonly DocumentEngineMigrationService $migrationService,
        private readonly EngineResolver $engineResolver,
        private readonly DocumentEngine $documentEngine
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'class',
                null,
                InputOption::VALUE_REQUIRED,
                'Only sweep this class, instead of every class'
            )
            ->addOption(
                self::OPTION_FORCE,
                null,
                InputOption::VALUE_NONE,
                'Rewrite the affected documents. Without it the command reports and changes nothing'
            )
            ->configureDryRunOption('Report the relations that would be removed and remove nothing (the default)');
    }

    /**
     * Reporting is the default and removing has to be asked for, which is what --force switches the
     * dry run of the trait off with: this rewrites stored documents, and an operator who ran it to
     * see what it finds must not have changed anything by doing so.
     *
     * The --dry-run the trait registers still has the last word, so that the two options cannot
     * contradict each other into a write: asking for a preview is never answered with one.
     */
    protected function isDryRun(): bool
    {
        return $this->input->getOption(self::OPTION_FORCE) !== true
            || $this->input->getOption(self::OPTION_DRY_RUN) === true;
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $classId = $input->getOption('class');
        $classId = is_string($classId) && $classId !== '' ? $classId : null;
        $classes = $this->migrationService->getClasses($classId);

        if ($classes === [] && $classId !== null) {
            $this->io->error('No class definition found for ' . $classId);

            return self::FAILURE;
        }

        $this->reportSkippedStores();

        $findings = [];
        $skipped = [];

        foreach ($classes as $class) {
            $sweep = $this->sweepClass($class);
            $findings = array_merge($findings, $sweep->getFindings());
            $skipped = array_merge($skipped, $sweep->getSkipped());
        }

        return $this->report(new DanglingRelationSweep($findings, $skipped));
    }

    /**
     * A store on an engine that does not offer relations holds no descriptor to sweep: its keys
     * cannot carry a relation at all, {@see \Pimcore\Model\DataObject\Classificationstore\RelationResolver::assertStorable()}.
     * It is named rather than silently left out, so that an operator who expected it to be swept
     * can see why it was not.
     *
     * @throws Exception
     */
    private function reportSkippedStores(): void
    {
        $skipped = [];

        foreach ((new StoreConfig\Listing())->getList() as $store) {
            $storeId = (int) $store->getId();

            if (!$this->engineResolver->resolveForStore($storeId)->supports(EngineFeature::Relations)) {
                $skipped[] = sprintf('store %d ("%s")', $storeId, (string) $store->getName());
            }
        }

        if ($skipped !== []) {
            $this->io->writeln('Skipped, the engine stores no relation: ' . implode(', ', $skipped));
        }
    }

    /**
     * @throws Exception
     */
    private function sweepClass(ClassDefinition $class): DanglingRelationSweep
    {
        $fieldNames = $this->migrationService->getDocumentFieldNames($class);

        if ($fieldNames === []) {
            return new DanglingRelationSweep([], []);
        }

        return $this->documentEngine->sweepDanglingRelations($class, $fieldNames, $this->isDryRun());
    }

    /**
     * A run that could not remove everything it found fails, so that whoever reads only the exit
     * code is told to run it again instead of reading a partial sweep as a finished one.
     */
    private function report(DanglingRelationSweep $sweep): int
    {
        $findings = $sweep->getFindings();

        if ($findings === []) {
            $this->io->success('Every stored Classification Store relation resolves.');

            return self::SUCCESS;
        }

        $this->io->section('Relations whose target no longer exists');

        foreach ($findings as $finding) {
            $this->io->writeln($this->dryRunMessage($finding->describe()));
        }

        $skipped = $sweep->getSkipped();

        $this->io->writeln($this->dryRunMessage(
            sprintf('%d relation(s) removed.', count($findings) - count($skipped))
        ));

        if ($skipped === []) {
            return self::SUCCESS;
        }

        $documents = $sweep->describeSkippedDocuments();

        $this->io->warning(sprintf(
            '%d document(s) changed while they were being swept and were left alone - run the command'
            . " again:\n%s",
            count($documents),
            implode("\n", $documents)
        ));

        return self::FAILURE;
    }
}
