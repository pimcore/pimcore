<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Console\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model\Asset;

class ThumbnailsOptimizeImagesCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('thumbnails:optimize-images')
            ->setDescription('Optimize filesize of all images in ' . PIMCORE_TEMPORARY_DIRECTORY);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = rscandir(PIMCORE_TEMPORARY_DIRECTORY . "/image-thumbnails/");

        $savedBytesTotal = 0;

        foreach ($files as $file) {
            if (file_exists($file)) {
                $originalFilesize = filesize($file);
                \Pimcore\Image\Optimizer::optimize($file);

                $savedBytes = ($originalFilesize-filesize($file));
                $savedBytesTotal += $savedBytes;

                $this->output->writeln("Optimized image: " . $file . " saved " . formatBytes($savedBytes));
            }
        }

        $this->output->writeln("Finished!");
        $this->output->writeln("Saved " . formatBytes($savedBytesTotal) . " in total");
    }
}
