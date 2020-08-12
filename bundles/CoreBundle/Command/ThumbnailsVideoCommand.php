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
use Pimcore\Console\Traits\Parallelization;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Version;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ThumbnailsVideoCommand extends AbstractCommand
{
    use Parallelization;

    protected function configure()
    {
        parent::configure();
        self::configureParallelization($this);

        $this
            ->setName('pimcore:thumbnails:video')
            ->setAliases(['thumbnails:video'])
            ->setDescription('Generate video thumbnails, useful to pre-generate thumbnails in the background')
            ->addOption(
                'parent',
                'p',
                InputOption::VALUE_OPTIONAL,
                'only create thumbnails of images in this folder (ID)'
            )
            ->addOption(
                'thumbnails',
                't',
                InputOption::VALUE_OPTIONAL,
                'only create specified thumbnails (comma separated eg.: thumb1,thumb2)'
            )->addOption(
                'system',
                's',
                InputOption::VALUE_NONE,
                'create system thumbnails (used for tree-preview, ...)'
            );
    }

    protected function fetchItems(InputInterface $input): array
    {
        $list = new Asset\Listing();

        // get only videos
        $conditions = ["type = 'video'"];
        if ($input->getOption('parent')) {
            $parent = Asset::getById($input->getOption('parent'));
            if ($parent instanceof Asset\Folder) {
                $conditions[] = "path LIKE '" . $list->escapeLike($parent->getRealFullPath()) . "/%'";
            } else {
                $this->writeError($input->getOption('parent') . ' is not a valid asset folder ID!');
                exit(1);
            }
        }

        $list->setCondition(implode(' AND ', $conditions));

        return $list->loadIdList();
    }

    protected function runSingleCommand(string $assetId, InputInterface $input, OutputInterface $output): void
    {
        // disable versioning
        Version::disable();

        $video = Asset\Video::getById($assetId);
        if (!$video) {
            $this->writeError('No video with ID=' . $assetId . ' found. Has the video been deleted or is the asset of another type?</error>');

            return;
        }

        // get all thumbnails
        $thumbnails = [];

        $list = new Asset\Video\Thumbnail\Config\Listing();
        $items = $list->getThumbnails();

        foreach ($items as $item) {
            $thumbnails[] = $item->getName();
        }

        $allowedThumbs = [];
        if ($input->getOption('thumbnails')) {
            $allowedThumbs = explode(',', $input->getOption('thumbnails'));
        }

        foreach ($thumbnails as $thumbnail) {
            if ((empty($allowedThumbs) && !$input->getOption('system')) || in_array($thumbnail, $allowedThumbs)) {
                if ($output->isVerbose()) {
                    $this->output->writeln('generating thumbnail for video: ' . $video->getRealFullPath() . ' | ' . $video->getId() . ' | Thumbnail: ' . $thumbnail . ' : ' . formatBytes(memory_get_usage()));
                }
                $video->getThumbnail($thumbnail);
                $this->waitTillFinished($video->getId(), $thumbnail);
            }
        }

        if ($input->getOption('system')) {
            if ($output->isVerbose()) {
                $this->output->writeln('generating thumbnail for video: ' . $video->getRealFullPath() . ' | ' . $video->getId() . ' | Thumbnail: System Preview : ' . formatBytes(memory_get_usage()));
            }
            $thumbnail = Asset\Video\Thumbnail\Config::getPreviewConfig();
            $video->getThumbnail($thumbnail);
            $this->waitTillFinished($video->getId(), $thumbnail);
        }
    }

    /**
     * @param int $videoId
     * @param string $thumbnail
     */
    protected function waitTillFinished($videoId, $thumbnail)
    {
        $finished = false;

        // initial delay
        $video = Asset::getById($videoId);
        $thumb = $video->getThumbnail($thumbnail);
        if ($thumb['status'] != 'finished') {
            sleep(20);
        }

        while (!$finished) {
            \Pimcore::collectGarbage();

            $video = Asset::getById($videoId);
            $thumb = $video->getThumbnail($thumbnail);
            if ($thumb['status'] == 'finished') {
                $finished = true;
                Logger::debug('video [' . $video->getId() . '] FINISHED');
            } elseif ($thumb['status'] == 'inprogress') {
                Logger::debug('video [' . $video->getId() . '] in progress ...');
                sleep(5);
            } else {
                // error
                Logger::debug('video [' . $video->getId() . "] has status: '" . $thumb['status'] . "' -> skipping");
                break;
            }
        }
    }

    protected function getItemName(int $count): string
    {
        return $count == 1 ? 'video' : 'videos';
    }
}
