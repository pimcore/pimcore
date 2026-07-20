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

namespace Pimcore\Bundle\CoreBundle\Command\Migrate;

use DateTime;
use League\Flysystem\StorageAttributes;
use Pimcore\Console\AbstractCommand;
use Pimcore\Tool\Storage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 *
 * @deprecated since Pimcore 12
 * TODO: Remove in Pimcore 13
 */
#[AsCommand(
    name: 'pimcore:migrate:mail-logs-folder-structure',
    description: 'Change mail logs folder structure to
    YYYY/MM/DD/<log filename>
    instead of
    <log filename>'
)]
class MailLogsFolderStructureCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Migrating mail logs to the new folder structure (YYYY/MM/DD/<filename>)...');
        $this->doMigrateStorage($output);
        $output->writeln("\n<info>Successfully moved log files to new folder structure</info>\n");

        return 0;
    }

    private function doMigrateStorage(OutputInterface $output): void
    {
        $storage = Storage::get('email_log');
        $logFiles = $storage->listContents('/', false)->filter(function (StorageAttributes $attributes) {
            if ($attributes->isDir()) {
                return false;
            }

            return true;
        });

        $iterator = $logFiles->toArray();
        $progressBar = new ProgressBar($output, count($iterator));

        $progressBar->start();

        /** @var StorageAttributes $logFile */
        foreach ($iterator as $logFile) {
            $date = (new DateTime())->setTimestamp($logFile->lastModified());
            $formattedDate = $date->format('Y' . DIRECTORY_SEPARATOR . 'm' . DIRECTORY_SEPARATOR . 'd');

            $targetPath = $formattedDate . DIRECTORY_SEPARATOR . $logFile->path();

            if (!$storage->fileExists($targetPath)) {
                $storage->move($logFile->path(), $targetPath);
            }

            $progressBar->advance();
        }
        $progressBar->finish();
    }
}
