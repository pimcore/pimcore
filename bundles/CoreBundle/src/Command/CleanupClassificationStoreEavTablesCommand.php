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
use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Traits\DryRun;
use Pimcore\Model\DataObject\ClassDefinition;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:classificationstore:cleanup',
    description: 'Drop the EAV value tables of a class whose Classification Stores are all converted',
    aliases: ['classificationstore:cleanup']
)]
class CleanupClassificationStoreEavTablesCommand extends AbstractCommand
{
    use DryRun;

    public function __construct(private readonly DocumentEngineMigrationService $migrationService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'class',
                null,
                InputOption::VALUE_REQUIRED,
                'Only clean up this class, instead of every class'
            )
            ->configureDryRunOption('Report the tables that would be dropped and drop nothing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $classId = $input->getOption('class');
        $classId = is_string($classId) && $classId !== '' ? $classId : null;
        $classes = $this->migrationService->getClasses($classId);

        if ($classes === [] && $classId !== null) {
            $this->io->error('No class definition found for ' . $classId);

            return self::FAILURE;
        }

        $refused = false;

        foreach ($classes as $class) {
            $refused = !$this->cleanUpClass($class) || $refused;
        }

        if ($refused) {
            $this->io->error('The EAV tables of at least one class were kept.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * The two EAV tables of a class go only when every store it uses is on the document engine and
     * the verification of that class holds: the flip is per store while this is per class, so a
     * second field on a store nobody flipped still needs the tables - which is why a refusal names
     * the stores rather than only refusing.
     */
    private function cleanUpClass(ClassDefinition $class): bool
    {
        $classId = $class->getId();

        // a class carrying no classification store field at all is none of this command's business,
        // whatever tables of that shape it may have been left with
        if ($this->migrationService->getClassificationStoreFieldNames($class) === []) {
            return true;
        }

        $blocking = $this->migrationService->getStoresBlockingCleanup($class);

        if ($blocking !== []) {
            $this->io->writeln(sprintf(
                '<comment>%s kept</comment>: still used by %s',
                $classId,
                implode(', ', $this->nameStores($blocking))
            ));

            return false;
        }

        $report = $this->migrationService->verify($class);
        if ($report->hasFailed()) {
            foreach ($report->failures as $failure) {
                $this->io->writeln(sprintf('<error>%s</error>: %s', $classId, $failure));
            }

            return false;
        }

        $tables = $this->migrationService->dropEavTables($class, $this->isDryRun());

        if ($tables === []) {
            $this->io->writeln(sprintf('%s: no EAV table left to drop', $classId));

            return true;
        }

        $this->io->writeln($this->dryRunMessage(sprintf('%s: dropping %s', $classId, implode(', ', $tables))));

        return true;
    }

    /**
     * @param array<int, string> $stores store id => store name
     *
     * @return string[]
     */
    private function nameStores(array $stores): array
    {
        $names = [];

        foreach ($stores as $storeId => $name) {
            $names[] = sprintf('store %d ("%s")', $storeId, $name);
        }

        return $names;
    }
}
