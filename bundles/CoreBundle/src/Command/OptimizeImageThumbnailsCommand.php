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

use League\Flysystem\StorageAttributes;
use Pimcore\Console\AbstractCommand;
use Pimcore\Image\ImageOptimizerInterface;
use Pimcore\Tool\Storage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:thumbnails:optimize-images',
    description: 'Optimize filesize of all thumbnails',
    aliases: ['thumbnails:optimize-images']
)]
class OptimizeImageThumbnailsCommand extends AbstractCommand
{
    public function __construct(private ImageOptimizerInterface $optimizer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storage = Storage::get('thumbnail');
        $savedBytesTotal = 0;

        /** @var StorageAttributes $item */
        foreach ($storage->listContents('/', true) as $item) {
            if ($item->isFile()) {
                $originalFilesize = $storage->fileSize($item->path());

                $this->optimizer->optimizeImage($item->path());

                clearstatcache();

                $savedBytes = ($originalFilesize - $storage->fileSize($item->path()));
                $savedBytesTotal += $savedBytes;

                $this->output->writeln('Optimized image: ' . $item->path() . ' saved ' . formatBytes($savedBytes));
            }
        }

        $this->output->writeln('Finished!');
        $this->output->writeln('Saved ' . formatBytes($savedBytesTotal) . ' in total');

        return 0;
    }
}
