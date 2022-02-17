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

namespace Pimcore\Bundle\CoreBundle\Command\Migrate;

use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToMoveFile;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\Asset;
use Pimcore\Tool\Storage;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class ThumbnailsFolderStructureCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('pimcore:migrate:thumbnails-folder-structure')
            ->setDescription('Change thumbnail folder structure to <asset storage>/<path to asset>/<asset id>/image-thumb__<asset id>__<thumbnail name>/<asset filename> instead of <asset storage>/<path to asset>/image-thumb__<asset id>__<thumbnail name>/<asset filename>');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $storage = Storage::get('thumbnail');

        $thumbnailFiles = $storage->listContents('/', true)->filter(function (StorageAttributes $attributes) {
            return $attributes->isDir() && preg_match('/image-thumb__\d+__/', $attributes->path(), $matches) && !str_contains($attributes->path(), '/'.$matches[1].'/image-thumb__'.$matches[1].'__');
        });

        $progressBar = new ProgressBar($output, iterator_count($thumbnailFiles));

        $progressBar->start();

        /** @var StorageAttributes $thumbnailFile */
        foreach ($thumbnailFiles as $thumbnailFile) {
            $targetPath = preg_replace('/image-thumb__(\d+)__(.+)$/', '$1/image-thumb__$1__$2', $thumbnailFile->path());

            if(!$storage->fileExists($targetPath)) {
                $storage->move($thumbnailFile->path(), $targetPath);
            } else {
                $storage->deleteDirectory($thumbnailFile->path());
            }

            $progressBar->advance();
        }
        $progressBar->finish();

        $output->writeln('Successfully moved thumbnail files to new folder structure');

        return 0;
    }
}
