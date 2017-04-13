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
use Pimcore\Model\Asset;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ThumbnailsImageCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('thumbnails:image')
            ->setDescription('Generate image thumbnails, useful to pre-generate thumbnails in the background')
            ->addOption(
                'parent', 'p',
                InputOption::VALUE_OPTIONAL,
                'only create thumbnails of images in this folder (ID)'
            )
            ->addOption(
                'thumbnails', 't',
                InputOption::VALUE_OPTIONAL,
                'only create specified thumbnails (comma separated eg.: thumb1,thumb2)'
            )->addOption(
                'system', 's',
                InputOption::VALUE_NONE,
                'create system thumbnails (used for tree-preview, ...)'
            )->addOption(
                'force', 'f',
                InputOption::VALUE_NONE,
                'recreate thumbnails, regardless if they exist already'
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $list = new Asset\Image\Thumbnail\Config\Listing();
        $items = $list->load();
        $thumbnails = [];
        foreach ($items as $item) {
            $thumbnails[] = $item->getName();
        }

        $allowedThumbs = [];
        if ($input->getOption('thumbnails')) {
            $allowedThumbs = explode(',', $input->getOption('thumbnails'));
        }

        // get only images
        $conditions = ["type = 'image'"];

        if ($input->getOption('parent')) {
            $parent = Asset::getById($input->getOption('parent'));
            if ($parent instanceof Asset\Folder) {
                $conditions[] = "path LIKE '" . $parent->getRealFullPath() . "/%'";
            } else {
                $this->writeError($input->getOption('parent') . ' is not a valid asset folder ID!');
                exit;
            }
        }

        $list = new Asset\Listing();
        $list->setCondition(implode(' AND ', $conditions));
        $total = $list->getTotalCount();
        $perLoop = 10;

        for ($i=0; $i < (ceil($total / $perLoop)); $i++) {
            $list->setLimit($perLoop);
            $list->setOffset($i * $perLoop);

            $images = $list->load();
            foreach ($images as $image) {
                foreach ($thumbnails as $thumbnail) {
                    if ((empty($allowedThumbs) && !$input->getOption('system')) || in_array($thumbnail, $allowedThumbs)) {
                        if ($input->getOption('force')) {
                            $image->clearThumbnail($thumbnail);
                        }

                        $this->output->writeln('generating thumbnail for image: ' . $image->getRealFullPath() . ' | ' . $image->getId() . ' | Thumbnail: ' . $thumbnail . ' : ' . formatBytes(memory_get_usage()));
                        $this->output->writeln('generated thumbnail: ' . $image->getThumbnail($thumbnail)->getFilesystemPath());
                    }
                }

                if ($input->getOption('system')) {
                    $thumbnail = Asset\Image\Thumbnail\Config::getPreviewConfig();
                    if ($input->getOption('force')) {
                        $image->clearThumbnail($thumbnail->getName());
                    }

                    $this->output->writeln('generating thumbnail for image: ' . $image->getRealFullPath() . ' | ' . $image->getId() . ' | Thumbnail: System Preview (tree) : ' . formatBytes(memory_get_usage()));
                    $this->output->writeln('generated thumbnail: ' . $image->getThumbnail($thumbnail)->getFilesystemPath());
                }
            }
            \Pimcore::collectGarbage();
        }
    }
}
