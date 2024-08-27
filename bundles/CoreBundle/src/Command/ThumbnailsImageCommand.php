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

use DateTime;
use Pimcore\Console\AbstractCommand;
use Pimcore\Console\Traits\Parallelization;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
#[AsCommand(
    name:'pimcore:thumbnails:image',
    description: 'Generate image thumbnails, useful to pre-generate thumbnails in the background',
    aliases: ['thumbnails:image']
)]
class ThumbnailsImageCommand extends AbstractCommand
{
    use Parallelization;

    private const DATE_FORMAT = 'Y-m-d H:i:s';

    protected function configure(): void
    {
        parent::configure();
        self::configureCommand($this);

        $this
            ->addOption(
                'parent',
                null,
                InputOption::VALUE_OPTIONAL,
                'only create thumbnails of images in this folder (comma separated IDs e.g. 543,1077)'
            )
            ->addOption(
                'id',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'only create thumbnails of images with this (IDs)'
            )
            ->addOption(
                'pathPattern',
                null,
                InputOption::VALUE_OPTIONAL,
                'Filter images against the given regex pattern (path + filename), example:  ^/Sample.*urban.jpg$'
            )
            ->addOption(
                'last-modified-since',
                null,
                InputOption::VALUE_OPTIONAL,
                'only create thumbnails of images that have been modified since the given date (format: ' . self::DATE_FORMAT . ' )'
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
            )->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'recreate thumbnails, regardless if they exist already'
            )->addOption(
                'skip-medias',
                null,
                InputOption::VALUE_NONE,
                'if config has media queries defined, do not generate thumbnails for them'
            )->addOption(
                'skip-high-res',
                null,
                InputOption::VALUE_NONE,
                'do not generate high-res (@2x) versions of thumbnails'
            );

        foreach (Image\Thumbnail\Config::getAutoFormats() as $autoFormat => $autoFormatConfig) {
            if ($autoFormatConfig['enabled']) {
                $this->addOption(
                    'skip-' . $autoFormat,
                    null,
                    InputOption::VALUE_NONE,
                    sprintf('if target image format is set to auto in config, do not generate %s images for them', $autoFormat)
                );
            }
        }
    }

    protected function fetchItems(InputInterface $input, OutputInterface $output): array
    {
        $list = new Asset\Listing();

        // Recently added or changed items are more likely to need thumbnails, start with those in case process is cut short
        $list->setOrderKey('modificationDate');
        $list->setOrder('DESC');

        $parentConditions = [];
        $conditionVariables = [];

        // get only images
        $conditions = ["type = 'image'"];

        if ($input->getOption('parent')) {
            $parentIds = explode(',', $input->getOption('parent'));
            foreach ($parentIds as $parentId) {
                $parent = Asset::getById((int) $parentId);
                if ($parent instanceof Asset\Folder) {
                    $parentConditions[] = "path LIKE '" . $list->escapeLike($parent->getRealFullPath()) . "/%'";
                } else {
                    $this->writeError($input->getOption('parent').' is not a valid asset folder ID!');
                    exit(1);
                }
            }
            $conditions[] = '('. implode(' OR ', $parentConditions) . ')';
        }

        if ($lastModifiedSince = $input->getOption('last-modified-since')) {
            $lastModifiedSinceDate = DateTime::createFromFormat(self::DATE_FORMAT, $lastModifiedSince);
            $conditions[] = 'modificationDate >= ?';
            $conditionVariables[] = $lastModifiedSinceDate->getTimestamp();
        }

        if ($regex = $input->getOption('pathPattern')) {
            $conditions[] = 'CONCAT(`path`, filename) REGEXP ?';
            $conditionVariables[] = $regex;
        }

        if ($ids = $input->getOption('id')) {
            $conditions[] = sprintf('id in (%s)', implode(',', $ids));
        }

        $list->setCondition(implode(' AND ', $conditions), $conditionVariables);

        $assetIdsList = $list->loadIdList();
        $thumbnailList = [];
        $thumbnailList[] = Asset\Image\Thumbnail\Config::getPreviewConfig();
        if (!$input->getOption('system')) {
            $thumbnailList = new Asset\Image\Thumbnail\Config\Listing();
            $thumbnailList = $thumbnailList->getThumbnails();
        }

        $allowedThumbs = [];
        if ($input->getOption('thumbnails')) {
            $allowedThumbs = explode(',', $input->getOption('thumbnails'));
        }

        $items = [];
        foreach ($assetIdsList as $assetId) {
            foreach ($thumbnailList as $thumbnailConfig) {
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
        [$assetId, $thumbnailConfigName] = explode('~~~', $item, 2);

        $image = Image::getById((int) $assetId);
        if (!$image) {
            $this->writeError('No image with ID=' . $assetId . ' found. Has the image been deleted or is the asset of another type?');

            return;
        }

        $thumbnailsToGenerate = $this->fetchThumbnailConfigs($input, $thumbnailConfigName);

        if ($input->getOption('force')) {
            $image->clearThumbnail($thumbnailConfigName);
        }

        foreach ($thumbnailsToGenerate as $thumbnailConfig) {
            $thumbnail = $image->getThumbnail($thumbnailConfig);
            $path = $thumbnail->getPath(['deferredAllowed' => false]);

            // triggers fetching the thumbnail info and updating the asset cache table if width or height are not in the cache
            $thumbnail->getDimensions();

            if ($output->isVerbose()) {
                $output->writeln(
                    sprintf(
                        'generated thumbnail for image [%d] | file: %s',
                        $image->getId(),
                        $path
                    )
                );
            }
        }
    }

    /**
     * @return Asset\Image\Thumbnail\Config[]
     */
    private function fetchThumbnailConfigs(InputInterface $input, string $thumbnailConfigName): array
    {
        /** @var Image\Thumbnail\Config $thumbnailConfig */
        $thumbnailConfig = Image\Thumbnail\Config::getByName($thumbnailConfigName);
        $thumbnailsToGenerate = [$thumbnailConfig];

        $medias = array_merge(['default' => 'defaultMedia'], $thumbnailConfig->getMedias() ?: []);
        foreach ($medias as $mediaName => $media) {
            $configMedia = clone $thumbnailConfig;
            if ($mediaName !== 'default') {
                $configMedia->selectMedia($mediaName);
            }

            if ($input->getOption('skip-medias') && $mediaName !== 'default') {
                continue;
            }

            $resolutions = [1, 2];
            if ($input->getOption('skip-high-res')) {
                $resolutions = [1];
            }

            foreach ($resolutions as $resolution) {
                $resConfig = clone $configMedia;
                $resConfig->setHighResolution($resolution);
                $thumbnailsToGenerate[] = $resConfig;

                if ($resConfig->getFormat() === 'SOURCE') {
                    foreach ($resConfig->getAutoFormatThumbnailConfigs() as $autoFormat => $autoFormatThumbnailConfig) {
                        if (!$input->getOption('skip-' . $autoFormat)) {
                            $thumbnailsToGenerate[] = $autoFormatThumbnailConfig;
                        }
                    }
                }
            }
        }

        return $thumbnailsToGenerate;
    }

    protected function getItemName(?int $count): string
    {
        return $count === 1 ? 'thumbnail' : 'thumbnails';
    }
}
