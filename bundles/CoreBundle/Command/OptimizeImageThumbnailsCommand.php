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

namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Image\ImageOptimizerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class OptimizeImageThumbnailsCommand extends AbstractCommand
{
    /**
     * @var ImageOptimizerInterface
     */
    private $optimizer;

    /**
     * @param ImageOptimizerInterface $optimizer
     */
    public function __construct(ImageOptimizerInterface $optimizer)
    {
        parent::__construct();

        $this->optimizer = $optimizer;
    }

    protected function configure()
    {
        $this
            ->setName('pimcore:thumbnails:optimize-images')
            ->setAliases(['thumbnails:optimize-images'])
            ->setDescription('Optimize filesize of all images in ' . PIMCORE_TEMPORARY_DIRECTORY);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $finder = new Finder();
        $savedBytesTotal = 0;

        /** @var \SplFileInfo $file */
        foreach ($finder->files()->in(PIMCORE_TEMPORARY_DIRECTORY . '/image-thumbnails/') as $file) {
            $file = $file->getRealPath();

            if (file_exists($file)) {
                $originalFilesize = filesize($file);

                $this->optimizer->optimizeImage($file);

                clearstatcache();

                $savedBytes = ($originalFilesize - filesize($file));
                $savedBytesTotal += $savedBytes;

                $this->output->writeln('Optimized image: ' . $file . ' saved ' . formatBytes($savedBytes));
            }
        }

        $this->output->writeln('Finished!');
        $this->output->writeln('Saved ' . formatBytes($savedBytesTotal) . ' in total');

        return 0;
    }
}
