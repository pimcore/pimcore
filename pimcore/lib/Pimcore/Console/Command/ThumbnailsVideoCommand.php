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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Console\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\Version;
use Pimcore\Logger;

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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // disable versioning
        Version::disable();

        // get all thumbnails
        $thumbnails = [];

        $list = new Asset\Video\Thumbnail\Config\Listing();
        $items = $list->load();

        foreach ($items as $item) {
            $thumbnails[] = $item->getName();
        }

        $allowedThumbs = [];
        if ($input->getOption("thumbnails")) {
            $allowedThumbs = explode(",", $input->getOption("thumbnails"));
        }


        // get only images
        $conditions = ["type = 'video'"];

        if ($input->getOption("parent")) {
            $parent = Asset::getById($input->getOption("parent"));
            if ($parent instanceof Asset\Folder) {
                $conditions[] = "path LIKE '" . $parent->getRealFullPath() . "/%'";
            } else {
                $this->writeError($input->getOption("parent") . " is not a valid asset folder ID!");
                exit;
            }
        }

        $list = new Asset\Listing();
        $list->setCondition(implode(" AND ", $conditions));
        $total = $list->getTotalCount();
        $perLoop = 10;

        for ($i=0; $i<(ceil($total/$perLoop)); $i++) {
            $list->setLimit($perLoop);
            $list->setOffset($i*$perLoop);

            $videos = $list->load();
            foreach ($videos as $video) {
                foreach ($thumbnails as $thumbnail) {
                    if ((empty($allowedThumbs) && !$input->getOption("system")) || in_array($thumbnail, $allowedThumbs)) {
                        $this->output->writeln("generating thumbnail for video: " . $video->getRealFullPath() . " | " . $video->getId() . " | Thumbnail: " . $thumbnail . " : " . formatBytes(memory_get_usage()));
                        $video->getThumbnail($thumbnail);
                        $this->waitTillFinished($video->getId(), $thumbnail);
                    }
                }

                if ($input->getOption("system")) {
                    $this->output->writeln("generating thumbnail for video: " . $video->getRealFullPath() . " | " . $video->getId() . " | Thumbnail: System Preview : " . formatBytes(memory_get_usage()));
                    $thumbnail = Asset\Video\Thumbnail\Config::getPreviewConfig();
                    $video->getThumbnail($thumbnail);
                    $this->waitTillFinished($video->getId(), $thumbnail);
                }
            }
        }
    }

    /**
     * @param $videoId
     * @param $thumbnail
     */
    protected function waitTillFinished($videoId, $thumbnail)
    {
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
                Logger::debug("video [" . $video->getId() . "] FINISHED");
            } elseif ($thumb["status"] == "inprogress") {
                Logger::debug("video [" . $video->getId() . "] in progress ...");
                sleep(5);
            } else {
                // error
                Logger::debug("video [" . $video->getId() . "] has status: '" . $thumb["status"] . "' -> skipping");
                break;
            }
        }
    }
}
