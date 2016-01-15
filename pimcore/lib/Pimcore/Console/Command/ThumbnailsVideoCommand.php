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
use Pimcore\Model\Version;

class ThumbnailsVideoCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('thumbnails:video')
            ->setDescription('Generate video thumbnails, useful to pre-generate thumbnails in the background')
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // disable versioning
        Version::disable();

        // get all thumbnails
        $dir = Asset\Video\Thumbnail\Config::getWorkingDir();
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
        $conditions = array("type = 'video'");

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

            $videos = $list->load();
            foreach ($videos as $video) {
                foreach ($thumbnails as $thumbnail) {
                    if((empty($allowedThumbs) && !$input->getOption("system")) || in_array($thumbnail, $allowedThumbs)) {
                        $this->output->writeln("generating thumbnail for video: " . $video->getFullpath() . " | " . $video->getId() . " | Thumbnail: " . $thumbnail . " : " . formatBytes(memory_get_usage()));
                        $video->getThumbnail($thumbnail);
                        $this->waitTillFinished($video->getId(), $thumbnail);
                    }
                }

                if($input->getOption("system")) {
                    $this->output->writeln("generating thumbnail for video: " . $video->getFullpath() . " | " . $video->getId() . " | Thumbnail: System Preview : " . formatBytes(memory_get_usage()));
                    $thumbnail = Asset\Video\Thumbnail\Config::getPreviewConfig();
                    $video->getThumbnail($thumbnail);
                    $this->waitTillFinished($video->getId(), $thumbnail);
                }
            }
        }
    }

    protected function waitTillFinished($videoId, $thumbnail) {

        $finished = false;

        // initial delay
        $video = Asset::getById($videoId);
        $thumb = $video->getThumbnail($thumbnail);
        if ($thumb["status"] != "finished") {
            sleep(20);
        }

        while (!$finished) {
            \Pimcore::collectGarbage();

            $video = Asset::getById($videoId);
            $thumb = $video->getThumbnail($thumbnail);
            if ($thumb["status"] == "finished") {
                $finished = true;
                \Logger::debug("video [" . $video->getId() . "] FINISHED");
            } else if ($thumb["status"] == "inprogress") {
                $progress = Asset\Video\Thumbnail\Processor::getProgress($thumb["processId"]);
                \Logger::debug("video [" . $video->getId() . "] in progress: " . number_format($progress,0) . "%");

                sleep(5);
            } else {
                // error
                \Logger::debug("video [" . $video->getId() . "] has status: '" . $thumb["status"] . "' -> skipping");
                break;
            }
        }
    }
}
