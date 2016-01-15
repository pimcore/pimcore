<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Console\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model\Asset;

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
                "only create thumbnails of images in this folder (ID)"
            )
            ->addOption(
                'thumbnails', 't',
                InputOption::VALUE_OPTIONAL,
                "only create specified thumbnails (comma separated eg.: thumb1,thumb2)"
            )->addOption(
                'system', 's',
                InputOption::VALUE_NONE,
                "create system thumbnails (used for tree-preview, ...)"
            )->addOption(
                'force', 'f',
                InputOption::VALUE_NONE,
                "recreate thumbnails, regardless if they exist already"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // get all thumbnails
        $dir = Asset\Image\Thumbnail\Config::getWorkingDir();
        $thumbnails = array();
        $files = scandir($dir);
        foreach ($files as $file) {
            if(strpos($file, ".xml")) {
                $thumbnails[] = str_replace(".xml", "", $file);
            }
        }

        $allowedThumbs = array();
        if($input->getOption("thumbnails")) {
            $allowedThumbs = explode(",", $input->getOption("thumbnails"));
        }


        // get only images
        $conditions = array("type = 'image'");

        if($input->getOption("parent")) {
            $parent = Asset::getById($input->getOption("parent"));
            if($parent instanceof Asset\Folder) {
                $conditions[] = "path LIKE '" . $parent->getFullPath() . "/%'";
            } else {
                $this->writeError($input->getOption("parent") . " is not a valid asset folder ID!");
                exit;
            }
        }

        $list = new Asset\Listing();
        $list->setCondition(implode(" AND ", $conditions));
        $total = $list->getTotalCount();
        $perLoop = 10;

        for($i=0; $i<(ceil($total/$perLoop)); $i++) {
            $list->setLimit($perLoop);
            $list->setOffset($i*$perLoop);

            $images = $list->load();
            foreach ($images as $image) {
                foreach ($thumbnails as $thumbnail) {
                    if((empty($allowedThumbs) && !$input->getOption("system")) || in_array($thumbnail, $allowedThumbs)) {
                        if($input->getOption("force")) {
                            $image->clearThumbnail($thumbnail);
                        }

                        $this->output->writeln("generating thumbnail for image: " . $image->getFullpath() . " | " . $image->getId() . " | Thumbnail: " . $thumbnail . " : " . formatBytes(memory_get_usage()));
                        $this->output->writeln("generated thumbnail: " . $image->getThumbnail($thumbnail));
                    }
                }

                if($input->getOption("system")) {

                    $thumbnail = Asset\Image\Thumbnail\Config::getPreviewConfig();
                    if($input->getOption("force")) {
                        $image->clearThumbnail($thumbnail->getName());
                    }

                    $this->output->writeln("generating thumbnail for image: " . $image->getFullpath() . " | " . $image->getId() . " | Thumbnail: System Preview (tree) : " . formatBytes(memory_get_usage()));
                    $this->output->writeln("generated thumbnail: " . $image->getThumbnail($thumbnail));
                }
            }
            \Pimcore::collectGarbage();
        }
    }
}
