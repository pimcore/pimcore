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
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ThumbnailsImageCommand extends AbstractCommand
{
    use Parallelization;

    protected function configure()
    {
        parent::configure();
        self::configureParallelization($this);

        $this
            ->setName('pimcore:thumbnails:image')
            ->setAliases(['thumbnails:image'])
            ->setDescription('Generate image thumbnails, useful to pre-generate thumbnails in the background')
            ->addOption(
                'parent',
                'p',
                InputOption::VALUE_OPTIONAL,
                'only create thumbnails of images in this folder (ID)'
            )
            ->addOption(
                'id',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'only create thumbnails of images with this (IDs)'
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
                'skip-webp',
                null,
                InputOption::VALUE_NONE,
                'if target image format is set to auto in config, do not generate WEBP images for them'
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
    }

    protected function fetchItems(InputInterface $input): array
    {
        $list = new Asset\Listing();

        // get only images
        $conditions = ["type = 'image'"];

        if ($input->getOption('parent')) {
            $parent = Asset::getById($input->getOption('parent'));
            if ($parent instanceof Asset\Folder) {
                $conditions[] = "path LIKE '" . $list->escapeLike($parent->getRealFullPath()) . "/%'";
            } else {
                $this->writeError($input->getOption('parent').' is not a valid asset folder ID!');
                exit(1);
            }
        }

        if ($ids = $input->getOption('id')) {
            $conditions[] = sprintf('id in (%s)', implode(',', $ids));
        }

        $list->setCondition(implode(' AND ', $conditions));

        return $list->loadIdList();
    }

    protected function runSingleCommand(string $assetId, InputInterface $input, OutputInterface $output): void
    {
        $image = Image::getById($assetId);
        if (!$image) {
            $this->writeError('No image with ID=' . $assetId . ' found. Has the image been deleted or is the asset of another type?</error>');

            return;
        }

        $thumbnailsToGenerate = $this->fetchThumbnailConfigs($input);

        if ($input->getOption('force')) {
            $thumbnailConfigNames = array_unique(
                array_map(function ($thumbnailConfig) {
                    return $thumbnailConfig->getName();
                }, $thumbnailsToGenerate)
            );

            foreach ($thumbnailConfigNames as $thumbnailConfigName) {
                $image->clearThumbnail($thumbnailConfigName);
            }
        }

        foreach ($thumbnailsToGenerate as $thumbnailConfig) {
            $thumbnail = $image->getThumbnail($thumbnailConfig);
            $path = $thumbnail->getPath(false);

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
     * @param InputInterface $input
     *
     * @return Asset\Image\Thumbnail\Config[]
     */
    private function fetchThumbnailConfigs(InputInterface $input): array
    {
        $list = new Asset\Image\Thumbnail\Config\Listing();
        $thumbnailConfigList = $list->getThumbnails();

        $allowedThumbs = [];
        if ($input->getOption('thumbnails')) {
            $allowedThumbs = explode(',', $input->getOption('thumbnails'));
        }

        /**
         * @var Asset\Image\Thumbnail\Config[] $thumbnailsToGenerate
         */
        $thumbnailsToGenerate = [];

        $config = \Pimcore\Config::getSystemConfiguration('assets');
        $isWebPAutoSupport = $config['image']['thumbnails']['webp_auto_support'] ?? false;

        foreach ($thumbnailConfigList as $thumbnailConfig) {
            if (empty($allowedThumbs) || in_array($thumbnailConfig->getName(), $allowedThumbs)) {
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

                        if ($isWebPAutoSupport && !$input->getOption('skip-webp') && $resConfig->getFormat() === 'SOURCE') {
                            $webpConfig = clone $resConfig;
                            $webpConfig->setFormat('webp');
                            $thumbnailsToGenerate[] = $webpConfig;
                        }
                    }
                }
            }
        }

        if ($input->getOption('system')) {
            if (!$input->getOption('thumbnails')) {
                $thumbnailsToGenerate = [];
            }
            $thumbnailsToGenerate[] = Asset\Image\Thumbnail\Config::getPreviewConfig();
        } elseif (!$input->getOption('thumbnails')) {
            $thumbnailsToGenerate[] = Asset\Image\Thumbnail\Config::getPreviewConfig();
        }

        return $thumbnailsToGenerate;
    }

    protected function getItemName(int $count): string
    {
        return $count == 1 ? 'image' : 'images';
    }
}
