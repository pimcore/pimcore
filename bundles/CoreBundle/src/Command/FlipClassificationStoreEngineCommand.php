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

    public function __construct(private readonly DocumentEngineMigrationService $migrationService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'store',
            InputArgument::REQUIRED,
            'The id or the name of the Classification Store to flip'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $argument = (string) $input->getArgument('store');
        $store = $this->migrationService->resolveStore($argument);

        if (!$store) {
            $this->io->error(sprintf('No Classification Store found for "%s".', $argument));

            return self::FAILURE;
        }

        $this->io->warning(self::CONFIRMATION);

        if (!$this->io->confirm(sprintf('Flip store "%s" to the document engine?', (string) $store->getName()))) {
            $this->io->writeln('Nothing was changed.');

            return self::SUCCESS;
        }

        $classIds = $this->migrationService->flip($store);

        $this->io->success(sprintf(
            'Store "%s" is on the document engine. Document tables created for: %s',
            (string) $store->getName(),
            $classIds === [] ? 'no class carries a field on this store' : implode(', ', $classIds)
        ));
        $this->io->writeln(
            'The values the store already holds are read from the EAV tables until'
            . ' pimcore:classificationstore:drain converts them.'
        );

        return self::SUCCESS;
    }
}
