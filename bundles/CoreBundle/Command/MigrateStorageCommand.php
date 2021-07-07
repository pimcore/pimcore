<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\CoreBundle\Command;

use League\Flysystem\StorageAttributes;
use Pimcore\Console\AbstractCommand;
use Pimcore\Tool\Storage;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * @internal
 */
class MigrateStorageCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('pimcore:migrate:storage')
            ->setDescription('Migrate data from one storage to another')
            ->addArgument(
                'storage',
                InputArgument::IS_ARRAY,
                'A list of storages to be migrated'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $storages = $input->getArgument('storage');

        foreach ($storages as $storage) {
            $storageSourceName = $storage . '_source';
            $storageDestinationName = $storage . '_destination';

            try {
                $sourceStorage = Storage::get($storageSourceName);
                $destinationStorage = Storage::get($storageDestinationName);
            } catch (\Exception $e) {
                $this->io->warning(sprintf('Skipped migrating storage "%s", please make sure source and destination configuration exists for migration.', $storage));
                continue;
            }

            $this->io->newLine();
            $this->io->info(sprintf('Migrating storage "%s"', $storage));

            $progressBar = new ProgressBar($output);
            $progressBar->setFormat('%current% [%bar%] %message%');
            $progressBar->start();

            /** @var StorageAttributes $item */
            foreach ($sourceStorage->listContents('/', true) as $item) {
                if ($item->isFile()) {
                    $path = $item->path();

                    try {
                        $stream = $sourceStorage->readStream($path);

                        if (!$destinationStorage->fileExists($path)) {
                            $destinationStorage->writeStream($item->path(), $stream);

                            $progressBar->setMessage(sprintf('Migrating %s: %s', $storage , $item->path()));
                        } else {
                            $progressBar->setMessage(sprintf('Skipping %s: %s', $storage, $item->path()));
                        }
                    } catch (\Exception $e) {
                        $progressBar->setMessage(sprintf('Skipping %s: %s', $storage, $item->path()));
                    }
                    $progressBar->advance();
                }
            }

            $progressBar->finish();
        }

        $this->io->success('Finished Migrating Storage!');

        return 0;
    }

}
