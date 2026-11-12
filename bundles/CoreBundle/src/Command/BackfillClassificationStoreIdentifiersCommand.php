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
use Pimcore\Db;
use Pimcore\Model\DataObject\Classificationstore\IdentifierBackfill;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:classificationstore:backfill-identifiers',
    description: 'Assign a generated identifier to classification store rows which do not have one yet'
)]
class BackfillClassificationStoreIdentifiersCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        [$written, $unresolved] = $this->createBackfill()->run(Db::get());

        $this->report($written, $unresolved);

        return 0;
    }

    protected function createBackfill(): IdentifierBackfill
    {
        return new IdentifierBackfill();
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
            $this->io->writeln(IdentifierBackfill::formatUnresolved($row));
        }
    }
}
