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

use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Traits\DryRun;
use Pimcore\Model\DataObject\Classificationstore\ReferenceTranslatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:classificationstore:translate-identifiers',
    description: 'Rewrite stored classification store group and key references from integer ids to identifiers'
)]
class TranslateClassificationStoreIdentifiersCommand extends AbstractCommand
{
    use DryRun;

    /**
     * @param iterable<ReferenceTranslatorInterface> $translators
     */
    public function __construct(private readonly iterable $translators)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->configureDryRunOption('Report what would be translated and write nothing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = $this->isDryRun();

        foreach ($this->translators as $translator) {
            $report = $translator->translate($dryRun);

            $this->io->section($translator->getName());
            $this->io->writeln($this->dryRunMessage(sprintf('%d reference(s) translated.', $report->translated)));
            $this->io->writeln(sprintf('%d reference(s) unresolved.', count($report->unresolved)));

            foreach ($report->unresolved as $unresolved) {
                $this->io->writeln(
                    sprintf('%s "%s": %s', $unresolved->location, $unresolved->reference, $unresolved->reason)
                );
            }
        }

        return 0;
    }
}
