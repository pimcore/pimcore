<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\CoreBundle\Command;

use Pimcore;
use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Traits\Parallelization;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Version;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name: 'pimcore:thumbnails:video',
    description: 'Generate video thumbnails, useful to pre-generate thumbnails in the background',
    aliases: ['thumbnails:video']
)]
class ThumbnailsVideoCommand extends AbstractCommand
{
    use Parallelization;

    protected function configure(): void
    {
        parent::configure();
        self::configureCommand($this);

        $this
           ->addOption(
               'parent',
               null,
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

    protected function fetchItems(InputInterface $input, OutputInterface $output): array
    {
        $list = new Asset\Listing();

        // get only videos
        $conditions = ["type = 'video'"];
        if ($parentId = $input->getOption('parent')) {
            $parent = Asset::getById((int) $parentId);
            if ($parent instanceof Asset\Folder) {
                $conditions[] = "path LIKE '" . $list->escapeLike($parent->getRealFullPath()) . "/%'";
            } else {
                $this->writeError($input->getOption('parent') . ' is not a valid asset folder ID!');
                exit(1);
            }
        }

        $list->setCondition(implode(' AND ', $conditions));
        $assetIdsList = $list->loadIdList();

        // get all thumbnails
        $videoThumbnailList = new Asset\Video\Thumbnail\Config\Listing();

        $allowedThumbs = [];
        if ($input->getOption('thumbnails')) {
            $allowedThumbs = explode(',', $input->getOption('thumbnails'));
        }

        $items = [];
        foreach ($assetIdsList as $assetId) {
            foreach ($videoThumbnailList->getThumbnails() as $thumbnailConfig) {
                $thumbName = $thumbnailConfig->getName();
                if (empty($allowedThumbs) || in_array($thumbName, $allowedThumbs)) {
                    $items[] = $assetId . '~~~' . $thumbName;
                }
            }
        }

        return $items;
    }

    protected function runSingleCommand(string $item, InputInterface $input, OutputInterface $output): void
    {
        // disable versioning
        Version::disable();

        [$assetId, $thumbnailConfigName] = explode('~~~', $item, 2);

        $video = Asset\Video::getById((int) $assetId);
        if (!$video) {
            $this->writeError('No video with ID=' . $assetId . ' found. Has the video been deleted or is the asset of another type?');

            return;
        }

        $thumbnail = Asset\Video\Thumbnail\Config::getByName($thumbnailConfigName);

        if ($output->isVerbose()) {
            $this->output->writeln(' generating thumbnail for video: ' . $video->getRealFullPath() . ' | ' . $video->getId() . ' | Thumbnail: ' . $thumbnailConfigName . ' : ' . formatBytes(memory_get_usage()));
        }
        $video->getThumbnail($thumbnail);
        $this->waitTillFinished($video->getId(), $thumbnail);

        if ($input->getOption('system')) {
            if ($output->isVerbose()) {
                $this->output->writeln(' generating thumbnail for video: ' . $video->getRealFullPath() . ' | ' . $video->getId() . ' | Thumbnail: System Preview : ' . formatBytes(memory_get_usage()));
            }
            $thumbnail = Asset\Video\Thumbnail\Config::getPreviewConfig();
            $video->getThumbnail($thumbnail);
            $this->waitTillFinished($video->getId(), $thumbnail);
        }
    }

    protected function waitTillFinished(int $videoId, string|Asset\Video\Thumbnail\Config $thumbnail): void
    {
        $finished = false;

        // initial delay
        $video = Asset\Video::getById($videoId);

        if (!$video instanceof Asset\Video) {
            $message = 'video ['.$videoId.'] could not be found. Skipping ...';
            Logger::error($message);

            return;
        }

        $thumb = $video->getThumbnail($thumbnail);
        if ($thumb !== null && $thumb['status'] !== 'finished') {
            sleep(20);
        }

        while (!$finished) {
            Pimcore::collectGarbage();

            $video = Asset\Video::getById($videoId);

            $thumb = $video->getThumbnail($thumbnail);

            if ($thumb === null) {
                $message = 'video ['.$videoId.'] with thumbnail ['.(is_string($thumbnail) ? $thumbnail : $thumbnail->getName()).'] is invalid. Skipping ...';
                Logger::error($message);
                $this->output->writeln($message);

                break;
            }

            if ($thumb['status'] === 'finished') {
                $finished = true;
                Logger::debug('video [' . $video->getId() . '] FINISHED');
            } elseif ($thumb['status'] === 'inprogress') {
                Logger::debug('video [' . $video->getId() . '] in progress ...');
                sleep(5);
            } else {
                // error
                Logger::debug('video [' . $video->getId() . "] has status: ['" . $thumb['status'] . "'] -> skipping ...");

                break;
            }
        }
    }

    protected function getItemName(?int $count): string
    {
        return $count === 1 ? 'thumbnail' : 'thumbnails';
    }
}
