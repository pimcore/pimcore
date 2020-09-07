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
use Pimcore\Db;
use Pimcore\Model\Asset;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LowQualityImagePreviewCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('pimcore:image:low-quality-preview')
            ->setAliases(['pimcore:image:svg-preview'])
            ->setDescription('Regenerates low quality image previews for all image assets')
            ->addOption(
                'id',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'only create thumbnails of images with this (IDs)'
            )
            ->addOption(
                'parent',
                'p',
                InputOption::VALUE_OPTIONAL,
                'only create thumbnails of images in this folder (ID)'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'generate preview regardless if it already exists or not'
            )
            ->addOption('generator', 'g', InputOption::VALUE_OPTIONAL, 'Force a generator, either `svg` or `imagick`');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // get only images
        $conditions = ["type = 'image'"];

        if ($input->getOption('parent')) {
            $parent = Asset::getById($input->getOption('parent'));
            if ($parent instanceof Asset\Folder) {
                $conditions[] = "path LIKE '" . Db::get()->escapeLike($parent->getRealFullPath()) . "/%'";
            } else {
                $this->writeError($input->getOption('parent') . ' is not a valid asset folder ID!');
                exit;
            }
        }

        if ($ids = $input->getOption('id')) {
            $conditions[] = sprintf('id in (%s)', implode(',', $ids));
        }

        $generator = null;
        if ($input->getOption('generator')) {
            $generator = $input->getOption('generator');
        }

        $force = $input->getOption('force');

        $list = new Asset\Listing();
        $list->setCondition(implode(' AND ', $conditions));
        $total = $list->getTotalCount();
        $perLoop = 10;
        $progressBar = new ProgressBar($this->output, $total);

        for ($i = 0; $i < (ceil($total / $perLoop)); $i++) {
            $list->setLimit($perLoop);
            $list->setOffset($i * $perLoop);
            /** @var Asset\Image[] $images */
            $images = $list->load();
            foreach ($images as $image) {
                $progressBar->advance();
                if ($force || !file_exists($image->getLowQualityPreviewFileSystemPath())) {
                    try {
                        $this->output->writeln('generating low quality preview for image: ' . $image->getRealFullPath() . ' | ' . $image->getId());
                        $image->generateLowQualityPreview($generator);
                    } catch (\Exception $e) {
                        $this->output->writeln('<error>'.$e->getMessage().'</error>');
                    }
                }
            }
            \Pimcore::collectGarbage();
        }

        $progressBar->finish();

        return 0;
    }
}
