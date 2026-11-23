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
use Pimcore\Bundle\CoreBundle\DataObject\Classificationstore\StoreFlipConflictException;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:classificationstore:flip',
    description: 'Put a Classification Store on the document storage engine',
    aliases: ['classificationstore:flip']
)]
class FlipClassificationStoreEngineCommand extends AbstractCommand
{
    /**
     * There is no engine argument and no un-flip command: the flip is the one direction the
     * migration runs in, and a store put back on `eav` by hand would hide every value written
     * since the flip rather than return them.
     */
    private const CONFIRMATION =
        'Every value written to this store after the flip exists only as a document. There is no'
        . ' reverse migration: putting the store back on "eav" does not bring those values back, it'
        . ' hides them behind the EAV rows that are left.';

    /**
     * Which engine a store resolves to is memoized for the length of a request or a messenger
     * message, so a process that resolved the EAV engine before the flip keeps writing EAV rows
     * until it reaches that boundary. Those rows are invisible to document reads, and the drain
     * refuses to delete them, so the step between the flip and the drain is to let the writers
     * that predate it finish.
     */
    private const WAIT_FOR_OLD_WRITERS =
        'Before draining, let the requests and messenger messages that were already running finish'
        . ' - a process that resolved the EAV engine before this flip keeps writing EAV rows until'
        . ' its next reset, and those rows are not read back and not drained.';

    public function __construct(private readonly DocumentEngineMigrationService $migrationService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'store',
            InputArgument::REQUIRED | InputArgument::IS_ARRAY,
            'The ids or the names of the Classification Stores to flip. Stores sharing a'
            . ' classification store field name have to be named together: the value tables key'
            . ' their rows by field name, so such a group cannot be split across two engines'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stores = [];

        /** @var string[] $arguments */
        $arguments = (array) $input->getArgument('store');

        foreach ($arguments as $argument) {
            $store = $this->migrationService->resolveStore($argument);

            if (!$store) {
                $this->io->error(sprintf('No Classification Store found for "%s".', $argument));

                return self::FAILURE;
            }

            $stores[] = $store;
        }

        $this->io->warning(self::CONFIRMATION);

        if (!$this->io->confirm(sprintf('Flip %s to the document engine?', $this->nameStores($stores)))) {
            $this->io->writeln('Nothing was changed.');

            return self::SUCCESS;
        }

        try {
            $classIds = $this->migrationService->flip(...$stores);
        } catch (StoreFlipConflictException $exception) {
            $this->io->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->io->success(sprintf(
            '%s now on the document engine. Document tables created for: %s',
            $this->nameStores($stores),
            $classIds === [] ? 'no class carries a field on these stores' : implode(', ', $classIds)
        ));
        $this->io->writeln(
            'The values the store already holds are read from the EAV tables until'
            . ' pimcore:classificationstore:drain converts them.'
        );
        $this->io->warning(self::WAIT_FOR_OLD_WRITERS);

        return self::SUCCESS;
    }

    /**
     * @param StoreConfig[] $stores
     */
    private function nameStores(array $stores): string
    {
        return implode(', ', array_map(
            static fn (StoreConfig $store): string => sprintf('store "%s"', (string) $store->getName()),
            $stores
        ));
    }
}
